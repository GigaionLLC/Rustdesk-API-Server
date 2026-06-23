<?php

/*
 * Catalog of the RustDesk client configuration options a Strategy (policy) can push via the
 * heartbeat `config_options` map, organised to mirror the RustDesk client's own Settings
 * window (General / Security / Network) so the admin editor feels like the client.
 *
 * Keys are the exact wire keys the client honours (libs/hbb_common/src/config.rs `keys` +
 * password_security.rs); do NOT rename them.
 *
 * Control types:
 *   - 'toggle' : tri-state select "" (client default) / "Y" / "N". Works uniformly because
 *                option2bool() treats "enable-" keys as default-on (off only on "N") and
 *                "allow-" / "hide-" / direct-server as default-off (on only on "Y").
 *   - 'select' : a fixed choice list; "" is the client default.
 *   - 'number' / 'text' : free value; empty means "not set" (omitted from config_options).
 *
 * An option is only included in the pushed config when its value is non-empty, so leaving a
 * control on "Default" keeps the client's own setting.
 */

return [
    'tabs' => [
        [
            'key' => 'general',
            'label' => 'General',
            'icon' => 'ri-settings-3-line',
            'sections' => [
                [
                    'label' => 'Recording',
                    'options' => [
                        ['key' => 'allow-auto-record-incoming', 'label' => 'Automatically record incoming sessions', 'type' => 'toggle'],
                        ['key' => 'allow-auto-record-outgoing', 'label' => 'Automatically record outgoing sessions', 'type' => 'toggle'],
                    ],
                ],
                [
                    'label' => 'Performance & display',
                    'options' => [
                        ['key' => 'enable-abr', 'label' => 'Adaptive bitrate', 'type' => 'toggle'],
                        ['key' => 'enable-hwcodec', 'label' => 'Hardware codec', 'type' => 'toggle'],
                        ['key' => 'allow-always-software-render', 'label' => 'Always software render', 'type' => 'toggle'],
                        ['key' => 'allow-linux-headless', 'label' => 'Linux headless support', 'type' => 'toggle'],
                        ['key' => 'allow-remove-wallpaper', 'label' => 'Remove wallpaper during session', 'type' => 'toggle'],
                    ],
                ],
                [
                    'label' => 'Updates',
                    'options' => [
                        ['key' => 'enable-check-update', 'label' => 'Check for software update on startup', 'type' => 'toggle'],
                        ['key' => 'allow-auto-update', 'label' => 'Auto update', 'type' => 'toggle'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'security',
            'label' => 'Security',
            'icon' => 'ri-lock-2-line',
            'sections' => [
                [
                    'label' => 'Permissions',
                    'help' => 'What a remote operator is allowed to do during a session.',
                    'options' => [
                        ['key' => 'access-mode', 'label' => 'Permissions preset', 'type' => 'select', 'choices' => [
                            '' => 'Custom (default)', 'full' => 'Full access', 'view' => 'View only',
                        ]],
                        ['key' => 'enable-keyboard', 'label' => 'Enable keyboard/mouse', 'type' => 'toggle'],
                        ['key' => 'enable-clipboard', 'label' => 'Enable clipboard', 'type' => 'toggle'],
                        ['key' => 'enable-file-transfer', 'label' => 'Enable file transfer', 'type' => 'toggle'],
                        ['key' => 'enable-file-copy-paste', 'label' => 'Enable file copy & paste', 'type' => 'toggle'],
                        ['key' => 'enable-audio', 'label' => 'Enable audio', 'type' => 'toggle'],
                        ['key' => 'enable-camera', 'label' => 'Enable camera', 'type' => 'toggle'],
                        ['key' => 'enable-terminal', 'label' => 'Enable terminal', 'type' => 'toggle'],
                        ['key' => 'enable-tunnel', 'label' => 'Enable TCP tunneling', 'type' => 'toggle'],
                        ['key' => 'enable-remote-restart', 'label' => 'Enable remote restart', 'type' => 'toggle'],
                        ['key' => 'enable-record-session', 'label' => 'Enable recording session', 'type' => 'toggle'],
                        ['key' => 'enable-block-input', 'label' => 'Enable blocking user input', 'type' => 'toggle'],
                        ['key' => 'enable-privacy-mode', 'label' => 'Enable privacy mode', 'type' => 'toggle'],
                        ['key' => 'enable-remote-printer', 'label' => 'Enable remote printer', 'type' => 'toggle'],
                        ['key' => 'allow-remote-config-modification', 'label' => 'Enable remote configuration modification', 'type' => 'toggle'],
                    ],
                ],
                [
                    'label' => 'Password',
                    'options' => [
                        ['key' => 'verification-method', 'label' => 'Accept sessions via', 'type' => 'select', 'choices' => [
                            '' => 'Both (default)', 'use-temporary-password' => 'One-time password only', 'use-permanent-password' => 'Permanent password only',
                        ]],
                        ['key' => 'temporary-password-length', 'label' => 'One-time password length', 'type' => 'select', 'choices' => [
                            '' => 'Default (6)', '6' => '6', '8' => '8', '10' => '10',
                        ]],
                        ['key' => 'allow-numeric-one-time-password', 'label' => 'Numeric one-time password', 'type' => 'toggle'],
                        ['key' => 'approve-mode', 'label' => 'Approve mode', 'type' => 'select', 'choices' => [
                            '' => 'Password or click (default)', 'password' => 'Password only', 'click' => 'Accept manually only',
                        ]],
                        ['key' => 'enable-trusted-devices', 'label' => 'Trusted devices (2FA)', 'type' => 'toggle'],
                    ],
                ],
                [
                    'label' => 'Connection security',
                    'options' => [
                        ['key' => 'direct-server', 'label' => 'Enable direct IP access', 'type' => 'toggle'],
                        ['key' => 'direct-access-port', 'label' => 'Direct access port', 'type' => 'number'],
                        ['key' => 'enable-lan-discovery', 'label' => 'LAN discovery', 'type' => 'toggle'],
                        ['key' => 'whitelist', 'label' => 'IP whitelist (comma/space separated)', 'type' => 'text'],
                        ['key' => 'allow-auto-disconnect', 'label' => 'Auto-close on user inactivity', 'type' => 'toggle'],
                        ['key' => 'auto-disconnect-timeout', 'label' => 'Inactivity timeout (minutes)', 'type' => 'number'],
                        ['key' => 'allow-only-conn-window-open', 'label' => 'Only allow connection if window open', 'type' => 'toggle'],
                        ['key' => 'allow-logon-screen-password', 'label' => 'Allow logon-screen password', 'type' => 'toggle'],
                        ['key' => 'allow-remote-cm-modification', 'label' => 'Allow modifying connection-manager', 'type' => 'toggle'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'network',
            'label' => 'Network',
            'icon' => 'ri-global-line',
            'sections' => [
                [
                    'label' => 'Network',
                    'options' => [
                        ['key' => 'allow-websocket', 'label' => 'Use WebSocket', 'type' => 'toggle'],
                        ['key' => 'enable-udp-punch', 'label' => 'UDP hole punching', 'type' => 'toggle'],
                        ['key' => 'enable-ipv6-punch', 'label' => 'IPv6 hole punching', 'type' => 'toggle'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'lockdown',
            'label' => 'Client UI',
            'icon' => 'ri-window-2-line',
            'sections' => [
                [
                    'label' => 'Hide settings tabs',
                    'help' => 'Stop users from seeing or changing these areas so they cannot alter policy.',
                    'options' => [
                        ['key' => 'hide-security-settings', 'label' => 'Hide security settings', 'type' => 'toggle'],
                        ['key' => 'hide-network-settings', 'label' => 'Hide network settings', 'type' => 'toggle'],
                        ['key' => 'hide-server-settings', 'label' => 'Hide server settings', 'type' => 'toggle'],
                        ['key' => 'hide-proxy-settings', 'label' => 'Hide proxy settings', 'type' => 'toggle'],
                        ['key' => 'hide-remote-printer-settings', 'label' => 'Hide remote-printer settings', 'type' => 'toggle'],
                        ['key' => 'hide-websocket-settings', 'label' => 'Hide WebSocket settings', 'type' => 'toggle'],
                        ['key' => 'hide-stop-service', 'label' => 'Hide "stop service"', 'type' => 'toggle'],
                    ],
                ],
                [
                    'label' => 'Hide UI elements',
                    'options' => [
                        ['key' => 'hide-tray', 'label' => 'Hide tray icon', 'type' => 'toggle'],
                        ['key' => 'hide-username-on-card', 'label' => 'Hide username on peer card', 'type' => 'toggle'],
                        ['key' => 'hide-help-cards', 'label' => 'Hide help cards', 'type' => 'toggle'],
                        ['key' => 'hide-powered-by-me', 'label' => 'Hide "powered by"', 'type' => 'toggle'],
                    ],
                ],
                [
                    'label' => 'Restrictions',
                    'options' => [
                        ['key' => 'disable-change-id', 'label' => 'Disable changing ID', 'type' => 'toggle'],
                        ['key' => 'disable-change-permanent-password', 'label' => 'Disable changing permanent password', 'type' => 'toggle'],
                    ],
                ],
            ],
        ],
    ],
];
