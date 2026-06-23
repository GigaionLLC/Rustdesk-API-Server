<?php

/*
 * Catalog of the RustDesk client configuration options a Strategy (policy) can push via the
 * heartbeat `config_options` map. Keys are the exact wire keys the client honours
 * (libs/hbb_common/src/config.rs `keys` + password_security.rs); do NOT rename them.
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
    'groups' => [
        [
            'label' => 'Permissions',
            'icon' => 'ri-shield-keyhole-line',
            'help' => 'What a remote operator is allowed to do during a session.',
            'options' => [
                ['key' => 'enable-keyboard', 'label' => 'Keyboard & mouse', 'type' => 'toggle'],
                ['key' => 'enable-clipboard', 'label' => 'Clipboard', 'type' => 'toggle'],
                ['key' => 'enable-file-transfer', 'label' => 'File transfer', 'type' => 'toggle'],
                ['key' => 'enable-file-copy-paste', 'label' => 'File copy & paste', 'type' => 'toggle'],
                ['key' => 'enable-audio', 'label' => 'Audio', 'type' => 'toggle'],
                ['key' => 'enable-camera', 'label' => 'Camera', 'type' => 'toggle'],
                ['key' => 'enable-terminal', 'label' => 'Terminal', 'type' => 'toggle'],
                ['key' => 'enable-tunnel', 'label' => 'TCP tunneling', 'type' => 'toggle'],
                ['key' => 'enable-remote-restart', 'label' => 'Remote restart', 'type' => 'toggle'],
                ['key' => 'enable-record-session', 'label' => 'Record session', 'type' => 'toggle'],
                ['key' => 'enable-block-input', 'label' => 'Block user input', 'type' => 'toggle'],
                ['key' => 'enable-remote-printer', 'label' => 'Remote printer', 'type' => 'toggle'],
                ['key' => 'allow-remote-config-modification', 'label' => 'Modify remote config', 'type' => 'toggle'],
                ['key' => 'allow-remote-cm-modification', 'label' => 'Modify connection-manager', 'type' => 'toggle'],
            ],
        ],
        [
            'label' => 'Security & access',
            'icon' => 'ri-lock-2-line',
            'help' => 'How connections are authenticated and approved.',
            'options' => [
                ['key' => 'access-mode', 'label' => 'Access mode', 'type' => 'select', 'choices' => [
                    '' => 'Custom (default)', 'full' => 'Full access', 'view' => 'View only',
                ]],
                ['key' => 'approve-mode', 'label' => 'Approve mode', 'type' => 'select', 'choices' => [
                    '' => 'Password or click (default)', 'password' => 'Password only', 'click' => 'Accept manually only',
                ]],
                ['key' => 'verification-method', 'label' => 'Password type', 'type' => 'select', 'choices' => [
                    '' => 'Both (default)', 'use-temporary-password' => 'One-time only', 'use-permanent-password' => 'Permanent only',
                ]],
                ['key' => 'temporary-password-length', 'label' => 'One-time password length', 'type' => 'select', 'choices' => [
                    '' => 'Default (6)', '6' => '6', '8' => '8', '10' => '10',
                ]],
                ['key' => 'allow-numeric-one-time-password', 'label' => 'Numeric one-time password', 'type' => 'toggle'],
                ['key' => 'enable-trusted-devices', 'label' => 'Trusted devices', 'type' => 'toggle'],
                ['key' => 'allow-auto-disconnect', 'label' => 'Auto-disconnect when idle', 'type' => 'toggle'],
                ['key' => 'auto-disconnect-timeout', 'label' => 'Idle timeout (minutes)', 'type' => 'number'],
                ['key' => 'allow-only-conn-window-open', 'label' => 'Only allow connection when window open', 'type' => 'toggle'],
                ['key' => 'allow-logon-screen-password', 'label' => 'Allow logon-screen password', 'type' => 'toggle'],
                ['key' => 'whitelist', 'label' => 'IP whitelist (comma/space separated)', 'type' => 'text'],
            ],
        ],
        [
            'label' => 'Recording',
            'icon' => 'ri-vidicon-line',
            'help' => 'Automatic session recording on the controlled device.',
            'options' => [
                ['key' => 'allow-auto-record-incoming', 'label' => 'Auto-record incoming sessions', 'type' => 'toggle'],
                ['key' => 'allow-auto-record-outgoing', 'label' => 'Auto-record outgoing sessions', 'type' => 'toggle'],
            ],
        ],
        [
            'label' => 'Privacy & display',
            'icon' => 'ri-eye-off-line',
            'help' => 'Screen/privacy behaviour and codec defaults.',
            'options' => [
                ['key' => 'enable-privacy-mode', 'label' => 'Privacy mode', 'type' => 'toggle'],
                ['key' => 'allow-remove-wallpaper', 'label' => 'Remove wallpaper during session', 'type' => 'toggle'],
                ['key' => 'enable-abr', 'label' => 'Adaptive bitrate', 'type' => 'toggle'],
                ['key' => 'enable-hwcodec', 'label' => 'Hardware codec', 'type' => 'toggle'],
                ['key' => 'allow-always-software-render', 'label' => 'Always software render', 'type' => 'toggle'],
                ['key' => 'allow-linux-headless', 'label' => 'Linux headless support', 'type' => 'toggle'],
            ],
        ],
        [
            'label' => 'Network',
            'icon' => 'ri-global-line',
            'help' => 'Direct connection and transport behaviour.',
            'options' => [
                ['key' => 'direct-server', 'label' => 'Direct IP access', 'type' => 'toggle'],
                ['key' => 'direct-access-port', 'label' => 'Direct access port', 'type' => 'number'],
                ['key' => 'enable-lan-discovery', 'label' => 'LAN discovery', 'type' => 'toggle'],
                ['key' => 'allow-websocket', 'label' => 'WebSocket transport', 'type' => 'toggle'],
                ['key' => 'enable-udp-punch', 'label' => 'UDP hole punching', 'type' => 'toggle'],
                ['key' => 'enable-ipv6-punch', 'label' => 'IPv6 hole punching', 'type' => 'toggle'],
            ],
        ],
        [
            'label' => 'Client UI lockdown',
            'icon' => 'ri-settings-3-line',
            'help' => 'Hide settings tabs and block changes so users cannot alter policy.',
            'options' => [
                ['key' => 'hide-security-settings', 'label' => 'Hide security settings', 'type' => 'toggle'],
                ['key' => 'hide-network-settings', 'label' => 'Hide network settings', 'type' => 'toggle'],
                ['key' => 'hide-server-settings', 'label' => 'Hide server settings', 'type' => 'toggle'],
                ['key' => 'hide-proxy-settings', 'label' => 'Hide proxy settings', 'type' => 'toggle'],
                ['key' => 'hide-remote-printer-settings', 'label' => 'Hide remote-printer settings', 'type' => 'toggle'],
                ['key' => 'hide-websocket-settings', 'label' => 'Hide WebSocket settings', 'type' => 'toggle'],
                ['key' => 'hide-stop-service', 'label' => 'Hide "stop service"', 'type' => 'toggle'],
                ['key' => 'hide-tray', 'label' => 'Hide tray icon', 'type' => 'toggle'],
                ['key' => 'hide-username-on-card', 'label' => 'Hide username on peer card', 'type' => 'toggle'],
                ['key' => 'hide-help-cards', 'label' => 'Hide help cards', 'type' => 'toggle'],
                ['key' => 'hide-powered-by-me', 'label' => 'Hide "powered by"', 'type' => 'toggle'],
                ['key' => 'disable-change-id', 'label' => 'Disable changing ID', 'type' => 'toggle'],
                ['key' => 'disable-change-permanent-password', 'label' => 'Disable changing permanent password', 'type' => 'toggle'],
                ['key' => 'enable-check-update', 'label' => 'Check for updates', 'type' => 'toggle'],
                ['key' => 'allow-auto-update', 'label' => 'Auto-update', 'type' => 'toggle'],
            ],
        ],
    ],
];
