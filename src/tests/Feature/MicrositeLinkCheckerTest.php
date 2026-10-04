<?php

use App\Services\MicrositeLinkChecker;
use Illuminate\Support\Facades\Http;

test('link s.id yang bisa diakses dianggap oke, termasuk yang ditulis tanpa https', function () {
    Http::fake([
        's.id/ISOC_Champion' => Http::response('', 303, ['Location' => 'https://chat.whatsapp.com/abc']),
        'chat.whatsapp.com/*' => Http::response('<html>WhatsApp Group</html>', 200),
    ]);

    $result = app(MicrositeLinkChecker::class)->check('s.id/ISOC_Champion');

    expect($result['ok'])->toBeTrue()
        ->and($result['url'])->toBe('https://s.id/ISOC_Champion');
});

test('halaman "Ups, link yang kamu akses Tidak Ditemukan" dari s.id dianggap belum', function () {
    Http::fake([
        's.id/*' => Http::response('<div id="root"></div><script id="data" type="application/json">{"__type":"not_found"}</script>', 404),
    ]);

    $result = app(MicrositeLinkChecker::class)->check('https://s.id/tidak-ada');

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toContain('Tidak Ditemukan');
});

test('penanda not_found tetap dianggap belum walau status 200, dan format salah ditolak', function () {
    Http::fake(['*' => Http::response('{"__type":"not_found"}', 200)]);

    expect(app(MicrositeLinkChecker::class)->check('s.id/hilang')['ok'])->toBeFalse()
        ->and(app(MicrositeLinkChecker::class)->check('bukan link')['ok'])->toBeFalse();
});
