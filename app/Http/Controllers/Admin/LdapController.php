<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LdapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Read-only LDAP / Active Directory status page plus a "Test connection" action.
 *
 * Configuration is environment-driven (config/ldap.php); this controller only surfaces the
 * effective settings (masking the bind password) and lets an admin verify the service-account
 * bind. It never mutates configuration.
 */
class LdapController extends Controller
{
    public function __construct(private readonly LdapService $ldap) {}

    public function index(): View
    {
        return view('admin.ldap.index', [
            'enabled' => (bool) config('ldap.enabled', false),
            'host' => (string) config('ldap.host', ''),
            'port' => (int) config('ldap.port', 389),
            'baseDn' => (string) config('ldap.base_dn', ''),
            'bindDn' => (string) config('ldap.bind_dn', ''),
            'bindPasswordSet' => (string) config('ldap.bind_password', '') !== '',
            'userFilter' => (string) config('ldap.user_filter', ''),
            'usernameAttr' => (string) config('ldap.username_attr', ''),
            'emailAttr' => (string) config('ldap.email_attr', ''),
            'displayNameAttr' => (string) config('ldap.displayname_attr', ''),
            'useStartTls' => (bool) config('ldap.use_starttls', false),
            'tlsVerify' => (bool) config('ldap.tls_verify', true),
            'adminGroup' => (string) config('ldap.admin_group', ''),
            'allowGroup' => (string) config('ldap.allow_group', ''),
            'sync' => (bool) config('ldap.sync', false),
            'extensionLoaded' => extension_loaded('ldap'),
        ]);
    }

    public function test(): RedirectResponse
    {
        $error = $this->ldap->testConnection();

        if ($error === null) {
            return redirect()
                ->route('admin.ldap.index')
                ->with('status', 'LDAP connection succeeded: service-account bind OK.');
        }

        return redirect()
            ->route('admin.ldap.index')
            ->with('error', $error);
    }
}
