<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LDAP\Connection;

/**
 * LDAP / Active Directory authentication and user synchronization.
 *
 * Mirrors the Go reference (service/ldap.go): connect, bind as the service account, search the
 * directory for the user, then re-bind as the found user DN with their password to verify the
 * credentials (the password is never read from the directory). Group membership drives admin
 * rights and the optional allow-group gate.
 *
 * Every public method is defensive: it never throws on directory/connection errors — it logs and
 * returns null/false so the caller can fall back to local-password auth. Passwords are never logged.
 */
class LdapService
{
    /**
     * Whether LDAP authentication is configured and enabled.
     */
    public function enabled(): bool
    {
        return (bool) config('ldap.enabled', false) && extension_loaded('ldap');
    }

    /**
     * Verify the username/password against the directory.
     *
     * Returns the mapped attributes on success:
     *   ['username','email','display_name','dn','is_admin','groups']
     * Returns null on any failure (bad credentials, user not found, not in allow-group,
     * connection/search error). Never throws.
     *
     * @return array{username:string,email:string,display_name:string,dn:string,is_admin:bool,groups:array<int,string>}|null
     */
    public function authenticate(string $username, string $password): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        // An empty password would otherwise trigger an LDAP "unauthenticated bind" that succeeds
        // without verifying anything — reject it up front.
        if ($username === '' || $password === '') {
            return null;
        }

        $connection = null;

        try {
            $connection = $this->connect();
            if ($connection === null) {
                return null;
            }

            // 1. Bind as the service account so we can search the directory.
            if (! $this->bindServiceAccount($connection)) {
                return null;
            }

            // 2. Locate the user entry.
            $entry = $this->findUser($connection, $username);
            if ($entry === null) {
                return null;
            }

            $userDn = (string) ($entry['dn'] ?? '');
            if ($userDn === '') {
                return null;
            }

            $groups = $this->extractGroups($entry);

            // 3. Honor the allow-group gate before attempting the user bind.
            $allowGroup = (string) config('ldap.allow_group', '');
            if ($allowGroup !== '' && ! $this->inGroup($groups, $allowGroup)) {
                Log::warning('LDAP login denied: user not in allow-group', ['username' => $username]);

                return null;
            }

            // 4. Re-bind as the user to verify their password. This is the credential check.
            if (! @ldap_bind($connection, $userDn, $password)) {
                return null;
            }

            return [
                'username' => $this->attr($entry, (string) config('ldap.username_attr', 'uid')) ?: $username,
                'email' => $this->attr($entry, (string) config('ldap.email_attr', 'mail')),
                'display_name' => $this->attr($entry, (string) config('ldap.displayname_attr', 'cn')),
                'dn' => $userDn,
                'is_admin' => $this->isAdmin($groups),
                'groups' => $groups,
            ];
        } catch (\Throwable $e) {
            Log::error('LDAP authenticate error: '.$e->getMessage());

            return null;
        } finally {
            if ($connection !== null) {
                @ldap_unbind($connection);
            }
        }
    }

    /**
     * Find-or-create the local User for a set of authenticated LDAP attributes.
     *
     * On create: stores a random local password (LDAP users authenticate via LDAP, not the local
     * hash). On an existing user, attributes are refreshed only when `sync` is enabled.
     *
     * @param  array{username:string,email:string,display_name:string,dn:string,is_admin:bool,groups:array<int,string>}  $attrs
     */
    public function syncUser(array $attrs): User
    {
        $username = $attrs['username'];

        /** @var User|null $user */
        $user = User::where('username', $username)->first();

        if ($user === null) {
            $user = new User;
            $user->username = $username;
            $user->email = $attrs['email'];
            $user->display_name = $attrs['display_name'];
            $user->is_admin = $attrs['is_admin'];
            $user->status = User::STATUS_NORMAL;
            // LDAP users never sign in with the local hash; store an unguessable random secret.
            $user->password = Str::random(40);
            $user->save();

            return $user;
        }

        // Existing user: only update attributes when sync is on.
        if ((bool) config('ldap.sync', false)) {
            $user->email = $attrs['email'];
            $user->display_name = $attrs['display_name'];
            $user->is_admin = $attrs['is_admin'];
            $user->save();
        }

        return $user;
    }

    /**
     * Attempt the service-account bind only (used by the admin "Test connection" action).
     * Returns null on success or a human-readable error message on failure.
     */
    public function testConnection(): ?string
    {
        if (! extension_loaded('ldap')) {
            return 'The PHP ldap extension is not installed.';
        }

        if (! (bool) config('ldap.enabled', false)) {
            return 'LDAP is disabled.';
        }

        $connection = null;

        try {
            $connection = $this->connect();
            if ($connection === null) {
                return 'Could not connect to the LDAP server.';
            }

            if (! $this->bindServiceAccount($connection)) {
                return 'Service-account bind failed: '.ldap_error($connection);
            }

            return null;
        } catch (\Throwable $e) {
            return 'LDAP error: '.$e->getMessage();
        } finally {
            if ($connection !== null) {
                @ldap_unbind($connection);
            }
        }
    }

    /**
     * Open the connection and (optionally) start TLS. Returns the connection or null on failure.
     */
    private function connect(): ?Connection
    {
        $host = (string) config('ldap.host', '');
        $port = (int) config('ldap.port', 389);

        // Respect certificate verification before any TLS negotiation.
        $verify = (bool) config('ldap.tls_verify', true);
        @ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, $verify ? LDAP_OPT_X_TLS_DEMAND : LDAP_OPT_X_TLS_NEVER);

        $connection = @ldap_connect($host, $port);
        if ($connection === false) {
            Log::error('LDAP connect failed', ['host' => $host, 'port' => $port]);

            return null;
        }

        @ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

        if ((bool) config('ldap.use_starttls', false)) {
            if (! @ldap_start_tls($connection)) {
                Log::error('LDAP StartTLS failed: '.ldap_error($connection));
                @ldap_unbind($connection);

                return null;
            }
        }

        return $connection;
    }

    /**
     * Bind using the configured service account.
     */
    private function bindServiceAccount(Connection $connection): bool
    {
        $bindDn = (string) config('ldap.bind_dn', '');
        $bindPassword = (string) config('ldap.bind_password', '');

        // An anonymous bind is allowed when no service account is configured.
        if ($bindDn === '') {
            return @ldap_bind($connection);
        }

        if (! @ldap_bind($connection, $bindDn, $bindPassword)) {
            Log::error('LDAP service-account bind failed: '.ldap_error($connection));

            return false;
        }

        return true;
    }

    /**
     * Search the base DN for the user with the configured filter. Returns the single matched
     * entry (associative, lowercased attribute keys + 'dn') or null when not exactly one match.
     *
     * @return array<string,mixed>|null
     */
    private function findUser(Connection $connection, string $username): ?array
    {
        $baseDn = (string) config('ldap.base_dn', '');
        $filterTemplate = (string) config('ldap.user_filter', '(uid=%s)');
        $filter = sprintf($filterTemplate, $this->escapeFilter($username));

        $search = @ldap_search($connection, $baseDn, $filter, [
            'dn',
            (string) config('ldap.username_attr', 'uid'),
            (string) config('ldap.email_attr', 'mail'),
            (string) config('ldap.displayname_attr', 'cn'),
            'memberof',
        ]);

        if ($search === false) {
            Log::error('LDAP search failed: '.ldap_error($connection));

            return null;
        }

        $entries = @ldap_get_entries($connection, $search);
        if (! is_array($entries) || ! isset($entries['count']) || (int) $entries['count'] !== 1) {
            return null;
        }

        return $entries[0];
    }

    /**
     * Pull a single attribute value from an ldap_get_entries() row (keys are lowercased).
     *
     * @param  array<string,mixed>  $entry
     */
    private function attr(array $entry, string $attribute): string
    {
        $key = strtolower($attribute);
        if (isset($entry[$key][0]) && is_string($entry[$key][0])) {
            return $entry[$key][0];
        }

        return '';
    }

    /**
     * Extract the user's group DNs from the memberOf attribute.
     *
     * @param  array<string,mixed>  $entry
     * @return array<int,string>
     */
    private function extractGroups(array $entry): array
    {
        $groups = [];
        if (isset($entry['memberof']) && is_array($entry['memberof'])) {
            $count = (int) ($entry['memberof']['count'] ?? 0);
            for ($i = 0; $i < $count; $i++) {
                if (isset($entry['memberof'][$i]) && is_string($entry['memberof'][$i])) {
                    $groups[] = $entry['memberof'][$i];
                }
            }
        }

        return $groups;
    }

    /**
     * Case-insensitive membership test against a group DN.
     *
     * @param  array<int,string>  $groups
     */
    private function inGroup(array $groups, string $groupDn): bool
    {
        foreach ($groups as $group) {
            if (strcasecmp($group, $groupDn) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine admin status from admin_group membership.
     *
     * @param  array<int,string>  $groups
     */
    private function isAdmin(array $groups): bool
    {
        $adminGroup = (string) config('ldap.admin_group', '');
        if ($adminGroup === '') {
            return false;
        }

        return $this->inGroup($groups, $adminGroup);
    }

    /**
     * Escape a value for safe inclusion in an LDAP filter (RFC 4515).
     */
    private function escapeFilter(string $value): string
    {
        if (function_exists('ldap_escape')) {
            return ldap_escape($value, '', LDAP_ESCAPE_FILTER);
        }

        return str_replace(
            ['\\', '*', '(', ')', "\x00"],
            ['\\5c', '\\2a', '\\28', '\\29', '\\00'],
            $value
        );
    }
}
