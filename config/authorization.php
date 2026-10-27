<?php

$account = ['account.view', 'account.security.manage'];
$author = [...$account, 'dashboard.access', 'trash.view', 'profiles.update', 'assets.upload', 'generated-assets.view', 'generated-assets.create', 'generated-assets.delete'];
foreach (['collections', 'poems', 'books', 'chapters'] as $resource) {
    foreach (['view', 'create', 'update', 'publish', 'archive', 'restore', 'preview'] as $ability) {
        $author[] = $resource.'.'.$ability;
    }
}
$admin = [...$author, 'content.manage-all'];
foreach (['view', 'create', 'update', 'finish', 'export'] as $ability) {
    $author[] = 'manuscripts.'.$ability;
    $admin[] = 'manuscripts.'.$ability;
}

foreach (['view', 'create', 'update'] as $ability) {
    $author[] = 'worlds.'.$ability;
    $admin[] = 'worlds.'.$ability;
}

foreach (['reviews.view', 'reviews.comment', 'reviews.suggest'] as $ability) {
    $author[] = $ability;
    $admin[] = $ability;
}

return [
    'roles' => [
        'reader' => $account,
        'editor' => [...$account, 'dashboard.access', 'reviews.view', 'reviews.comment', 'reviews.suggest'],
        'author' => $author,
        'admin' => $admin,
        'superadmin' => [...$admin, 'users.manage', 'roles.manage'],
    ],
];
