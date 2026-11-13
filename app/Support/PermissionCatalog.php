<?php

namespace App\Support;

/**
 * Frozen permission catalog (docs/05-PERMISSION-MATRIX.md).
 *
 * Permissions use the `resource.action` convention. Legacy role names are
 * mapped here until the identity migration introduces granular roles; the
 * catalog is the single source of truth for /me and Policies.
 */
class PermissionCatalog
{
    public const VERSION = '1.0';

    /** @var array<string, list<string>> */
    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin' => [
            'customer.view', 'customer.create', 'customer.update', 'customer.transfer',
            'partner.view', 'partner.create', 'partner.update', 'partner.suspend',
            'package.view', 'package.manage',
            'subscription.view', 'subscription.update', 'subscription.activate', 'subscription.suspend',
            'invoice.view', 'invoice.create', 'invoice.void',
            'invoice.public_link.create', 'invoice.public_link.revoke',
            'payment.view', 'payment.create', 'payment.reconcile', 'payment.refund',
            'subscription.create', 'subscription.restore', 'subscription.change_package',
            'partner.users.manage', 'partner.contract.view', 'partner.contract.manage', 'partner.contract.request',
            'tax.view', 'tax.create', 'tax.submit', 'tax.review', 'tax.report.partner',
            'report.billing',
            'integration.whatsapp.test',
            'network.device.manage', 'network.profile.manage',
            'network.session.disconnect', 'network.sync.retry',
            'audit.view',
        ],
        'mitra' => [
            'customer.view', 'customer.create', 'customer.update',
            'partner.view',
            'package.view',
            'subscription.view', 'subscription.update',
            'invoice.view', 'invoice.create',
            'invoice.public_link.create', 'invoice.public_link.revoke',
            'payment.view', 'payment.create',
            'subscription.create', 'subscription.restore', 'subscription.change_package',
            'partner.users.manage', 'partner.contract.view', 'partner.contract.request',
            'tax.view', 'tax.create', 'tax.submit',
            'report.billing',
        ],
    ];

    /**
     * @return list<string>
     */
    public static function forRole(?string $roleName): array
    {
        if ($roleName === null) {
            return [];
        }

        return self::ROLE_PERMISSIONS[$roleName] ?? [];
    }

    public static function allows(?string $roleName, string $permission): bool
    {
        $permissions = self::forRole($roleName);

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
