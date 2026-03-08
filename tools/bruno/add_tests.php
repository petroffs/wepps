<?php
$base = __DIR__ . '/WeppPlatformV1';

function testsBlock(array $checks): string {
    $lines = ["\ntests {"];
    foreach ($checks as [$label, $assertion]) {
        $lines[] = "  test(\"{$label}\", function() {";
        $lines[] = "    {$assertion};";
        $lines[] = "  });";
    }
    $lines[] = "}";
    return implode("\n", $lines) . "\n";
}

$s200      = ["status 200",                 "expect(res.status).to.equal(200)"];
$arr       = ["data is array",              'expect(res.body.data).to.be.an("array")'];
$obj       = ["data is object",             'expect(res.body.data).to.be.an("object")'];
$hasId     = ["data has Id",                'expect(res.body.data).to.have.property("Id")'];
$cnt       = ["count is number",            'expect(res.body.count).to.be.a("number")'];
$accTok    = ["returns access_token",       'expect(res.body.data).to.have.property("access_token")'];
$refTok    = ["returns refresh_token",      'expect(res.body.data).to.have.property("refresh_token")'];
$creId     = ["returns created id",         'expect(res.body.data).to.have.property("id")'];
$msg       = ["response has message",       'expect(res.body).to.have.property("message")'];
$hasStatus = ["response has status field",  'expect(res.body).to.have.property("status")'];

$files = [
    "auth.login.bru"                       => testsBlock([$s200, $accTok, $refTok]),
    "auth.refresh.bru"                     => testsBlock([$s200, $accTok, $refTok]),
    "auth.confirm.bru"                     => testsBlock([$hasStatus, $msg]),
    "APP/goods/goods.get.bru"              => testsBlock([$s200, $arr, $cnt]),
    "APP/goods/goods.item.get.bru"         => testsBlock([$s200, $hasId]),
    "APP/goods/goods.categories.get.bru"   => testsBlock([$s200, $arr]),
    "APP/goods/goods.filters.get.bru"      => testsBlock([$s200, $obj]),
    "APP/profile/profile.get.bru"          => testsBlock([$s200, $hasId]),
    "APP/profile/profile.post.bru"         => testsBlock([$s200, $msg]),
    "APP/profile/profile.put.bru"          => testsBlock([$s200]),
    "APP/profile/profile.password.put.bru" => testsBlock([$s200]),
    "APP/profile/profile.delete.bru"       => testsBlock([$s200]),
    "APP/orders/orders.get.bru"            => testsBlock([$s200, $arr, $cnt]),
    "APP/orders/orders.item.get.bru"       => testsBlock([$s200, $hasId]),
    "APP/orders/orders.post.bru"           => testsBlock([$s200, $msg]),
    "APP/orders/orders.delete.bru"         => testsBlock([$s200]),
    "APP/news/news.get.bru"                => testsBlock([$s200, $arr, $cnt]),
    "APP/news/news.item.get.bru"           => testsBlock([$s200, $hasId]),
    "APP/slides/slides.get.bru"            => testsBlock([$s200, $arr]),
    "M2M/goods/goods.post.bru"             => testsBlock([$s200, $creId]),
    "M2M/goods/goods.put.bru"              => testsBlock([$s200]),
    "M2M/goods/goods.delete.bru"           => testsBlock([$s200]),
    "M2M/orders/orders.status.put.bru"     => testsBlock([$s200]),
    "M2M/users/users.get.bru"              => testsBlock([$s200, $arr, $cnt]),
    "M2M/users/users.item.get.bru"         => testsBlock([$s200, $hasId]),
    "M2M/users/users.post.bru"             => testsBlock([$s200, $creId]),
    "M2M/users/users.put.bru"              => testsBlock([$s200]),
];

foreach ($files as $rel => $block) {
    $path = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!file_exists($path)) {
        echo "NOT FOUND: {$rel}\n";
        continue;
    }
    $content = file_get_contents($path);
    if (str_contains($content, 'tests {')) {
        echo "SKIP (already has tests): {$rel}\n";
        continue;
    }
    // Вставляем перед docs {, или в конец файла
    if (str_contains($content, "\ndocs {")) {
        $content = str_replace("\ndocs {", $block . "\ndocs {", $content);
    } else {
        $content = rtrim($content) . "\n" . $block;
    }
    file_put_contents($path, $content);
    echo "OK: {$rel}\n";
}

echo "Done.\n";
