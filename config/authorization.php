<?php

$account = ['account.view', 'account.security.manage'];
$author = [...$account, 'dashboard.access', 'trash.view', 'profiles.update', 'assets.upload', 'generated-assets.view', 'generated-assets.create', 'generated-assets.delete'];
foreach (['collections', 'poems', 'books', 'chapters'] as $resource) {
    foreach (['view', 'create', 'update', 'publish', 'archive', 'restore', 'preview'] as $ability) {
        $author[] = $resource.'.'.$ability;
    }
}
$admin = [...$author, 'content.manage-all'];

return [
    'roles' => [
        'reader' => $account,
        'author' => $author,
        'admin' => $admin,
        'superadmin' => [...$admin, 'users.manage', 'roles.manage'],
    ],
];
