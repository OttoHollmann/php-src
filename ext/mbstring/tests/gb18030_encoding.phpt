--TEST--
Exhaustive test of verification and conversion of GB18030 text
--EXTENSIONS--
mbstring
--SKIPIF--
<?php
if (getenv("SKIP_SLOW_TESTS")) die("skip slow test");
?>
--FILE--
<?php
include('encoding_tests.inc');
srand(1111); // Make results consistent
mb_substitute_character(0x25); // '%'

readConversionTable(__DIR__ . '/data/GB18030-2byte.txt', $toUnicode, $fromUnicode);

/* GB18030 represents all Unicode codepoints in the BMP which are _not_ covered by any
 * 2-byte GB18030 codepoint as a 4-byte code, with each of the 4 bytes in the following ranges:
 *
 * - 1st byte: 0x81-0x84
 * - 2nd byte: 0x30-0x39
 * - 3rd byte: 0x81-0xFE
 * - 4th byte: 0x30-0x39
 *
 * These start from 0x81308130 and count upwards one by one, with all the Unicode codepoints
 * which need to be represented as a 4-byte code appearing in sequence.
 *
 * Each subarray here is: [starting GB18030 codepoint bytes (4 of them), Unicode codepoint which it
 * converts to, number of sequential Unicode codepoints represented by sequential GB18030 codepoints] */
$gb18030_BMP_Mappings = [
  [0x81, 0x30, 0x81, 0x30, 0x80,  36],
  [0x81, 0x30, 0x84, 0x36, 0xa5,  2],
  [0x81, 0x30, 0x84, 0x38, 0xa9,  7],
  [0x81, 0x30, 0x85, 0x35, 0xb2,  5],
  [0x81, 0x30, 0x86, 0x30, 0xb8,  31],
  [0x81, 0x30, 0x89, 0x31, 0xd8,  8],
  [0x81, 0x30, 0x89, 0x39, 0xe2,  6],
  [0x81, 0x30, 0x8a, 0x35, 0xeb,  1],
  [0x81, 0x30, 0x8a, 0x36, 0xee,  4],
  [0x81, 0x30, 0x8b, 0x30, 0xf4,  3],
  [0x81, 0x30, 0x8b, 0x33, 0xf8,  1],
  [0x81, 0x30, 0x8b, 0x34, 0xfb,  1],
  [0x81, 0x30, 0x8b, 0x35, 0xfd,  4],
  [0x81, 0x30, 0x8b, 0x39, 0x102, 17],
  [0x81, 0x30, 0x8d, 0x36, 0x114, 7],
  [0x81, 0x30, 0x8e, 0x33, 0x11c, 15],
  [0x81, 0x30, 0x8f, 0x38, 0x12c, 24],
  [0x81, 0x30, 0x92, 0x32, 0x145, 3],
  [0x81, 0x30, 0x92, 0x35, 0x149, 4],
  [0x81, 0x30, 0x92, 0x39, 0x14e, 29],
  [0x81, 0x30, 0x95, 0x38, 0x16c, 98],
  [0x81, 0x30, 0x9f, 0x36, 0x1cf, 1],
  [0x81, 0x30, 0x9f, 0x37, 0x1d1, 1],
  [0x81, 0x30, 0x9f, 0x38, 0x1d3, 1],
  [0x81, 0x30, 0x9f, 0x39, 0x1d5, 1],
  [0x81, 0x30, 0xa0, 0x30, 0x1d7, 1],
  [0x81, 0x30, 0xa0, 0x31, 0x1d9, 1],
  [0x81, 0x30, 0xa0, 0x32, 0x1db, 1],
  [0x81, 0x30, 0xa0, 0x33, 0x1dd, 28],
  [0x81, 0x30, 0xa3, 0x31, 0x1fa, 87],
  [0x81, 0x30, 0xab, 0x38, 0x252, 15],
  [0x81, 0x30, 0xad, 0x33, 0x262, 101],
  [0x81, 0x30, 0xb7, 0x34, 0x2c8, 1],
  [0x81, 0x30, 0xb7, 0x35, 0x2cc, 13],
  [0x81, 0x30, 0xb8, 0x38, 0x2da, 183],
  [0x81, 0x30, 0xcb, 0x31, 0x3a2, 1],
  [0x81, 0x30, 0xcb, 0x32, 0x3aa, 7],
  [0x81, 0x30, 0xcb, 0x39, 0x3c2, 1],
  [0x81, 0x30, 0xcc, 0x30, 0x3ca, 55],
  [0x81, 0x30, 0xd1, 0x35, 0x402, 14],
  [0x81, 0x30, 0xd2, 0x39, 0x450, 1],
  [0x81, 0x30, 0xd3, 0x30, 0x452, 7102],
  [0x81, 0x36, 0xa5, 0x32, 0x2011, 2],
  [0x81, 0x36, 0xa5, 0x34, 0x2017, 1],
  [0x81, 0x36, 0xa5, 0x35, 0x201a, 2],
  [0x81, 0x36, 0xa5, 0x37, 0x201e, 7],
  [0x81, 0x36, 0xa6, 0x34, 0x2027, 9],
  [0x81, 0x36, 0xa7, 0x33, 0x2031, 1],
  [0x81, 0x36, 0xa7, 0x34, 0x2034, 1],
  [0x81, 0x36, 0xa7, 0x35, 0x2036, 5],
  [0x81, 0x36, 0xa8, 0x30, 0x203c, 112],
  [0x81, 0x36, 0xb3, 0x32, 0x20ad, 86],
  [0x81, 0x36, 0xbb, 0x38, 0x2104, 1],
  [0x81, 0x36, 0xbb, 0x39, 0x2106, 3],
  [0x81, 0x36, 0xbc, 0x32, 0x210a, 12],
  [0x81, 0x36, 0xbd, 0x34, 0x2117, 10],
  [0x81, 0x36, 0xbe, 0x34, 0x2122, 62],
  [0x81, 0x36, 0xc4, 0x36, 0x216c, 4],
  [0x81, 0x36, 0xc5, 0x30, 0x217a, 22],
  [0x81, 0x36, 0xc7, 0x32, 0x2194, 2],
  [0x81, 0x36, 0xc7, 0x34, 0x219a, 110],
  [0x81, 0x36, 0xd2, 0x34, 0x2209, 6],
  [0x81, 0x36, 0xd3, 0x30, 0x2210, 1],
  [0x81, 0x36, 0xd3, 0x31, 0x2212, 3],
  [0x81, 0x36, 0xd3, 0x34, 0x2216, 4],
  [0x81, 0x36, 0xd3, 0x38, 0x221b, 2],
  [0x81, 0x36, 0xd4, 0x30, 0x2221, 2],
  [0x81, 0x36, 0xd4, 0x32, 0x2224, 1],
  [0x81, 0x36, 0xd4, 0x33, 0x2226, 1],
  [0x81, 0x36, 0xd4, 0x34, 0x222c, 2],
  [0x81, 0x36, 0xd4, 0x36, 0x222f, 5],
  [0x81, 0x36, 0xd5, 0x31, 0x2238, 5],
  [0x81, 0x36, 0xd5, 0x36, 0x223e, 10],
  [0x81, 0x36, 0xd6, 0x36, 0x2249, 3],
  [0x81, 0x36, 0xd6, 0x39, 0x224d, 5],
  [0x81, 0x36, 0xd7, 0x34, 0x2253, 13],
  [0x81, 0x36, 0xd8, 0x37, 0x2262, 2],
  [0x81, 0x36, 0xd8, 0x39, 0x2268, 6],
  [0x81, 0x36, 0xd9, 0x35, 0x2270, 37],
  [0x81, 0x36, 0xdd, 0x32, 0x2296, 3],
  [0x81, 0x36, 0xdd, 0x35, 0x229a, 11],
  [0x81, 0x36, 0xde, 0x36, 0x22a6, 25],
  [0x81, 0x36, 0xe1, 0x31, 0x22c0, 82],
  [0x81, 0x36, 0xe9, 0x33, 0x2313, 333],
  [0x81, 0x37, 0x8c, 0x36, 0x246a, 10],
  [0x81, 0x37, 0x8d, 0x36, 0x249c, 100],
  [0x81, 0x37, 0x97, 0x36, 0x254c, 4],
  [0x81, 0x37, 0x98, 0x30, 0x2574, 13],
  [0x81, 0x37, 0x99, 0x33, 0x2590, 3],
  [0x81, 0x37, 0x99, 0x36, 0x2596, 10],
  [0x81, 0x37, 0x9a, 0x36, 0x25a2, 16],
  [0x81, 0x37, 0x9c, 0x32, 0x25b4, 8],
  [0x81, 0x37, 0x9d, 0x30, 0x25be, 8],
  [0x81, 0x37, 0x9d, 0x38, 0x25c8, 3],
  [0x81, 0x37, 0x9e, 0x31, 0x25cc, 2],
  [0x81, 0x37, 0x9e, 0x33, 0x25d0, 18],
  [0x81, 0x37, 0xa0, 0x31, 0x25e6, 31],
  [0x81, 0x37, 0xa3, 0x32, 0x2607, 2],
  [0x81, 0x37, 0xa3, 0x34, 0x260a, 54],
  [0x81, 0x37, 0xa8, 0x38, 0x2641, 1],
  [0x81, 0x37, 0xa8, 0x39, 0x2643, 2110],
  [0x81, 0x38, 0xfd, 0x39, 0x2e82, 2],
  [0x81, 0x38, 0xfe, 0x31, 0x2e85, 3],
  [0x81, 0x38, 0xfe, 0x34, 0x2e89, 2],
  [0x81, 0x38, 0xfe, 0x36, 0x2e8d, 10],
  [0x81, 0x39, 0x81, 0x36, 0x2e98, 15],
  [0x81, 0x39, 0x83, 0x31, 0x2ea8, 2],
  [0x81, 0x39, 0x83, 0x33, 0x2eab, 3],
  [0x81, 0x39, 0x83, 0x36, 0x2eaf, 4],
  [0x81, 0x39, 0x84, 0x30, 0x2eb4, 2],
  [0x81, 0x39, 0x84, 0x32, 0x2eb8, 3],
  [0x81, 0x39, 0x84, 0x35, 0x2ebc, 14],
  [0x81, 0x39, 0x85, 0x39, 0x2ecb, 293],
  [0x81, 0x39, 0xa3, 0x32, 0x2ffc, 4],
  [0x81, 0x39, 0xa3, 0x36, 0x3004, 1],
  [0x81, 0x39, 0xa3, 0x37, 0x3018, 5],
  [0x81, 0x39, 0xa4, 0x32, 0x301f, 2],
  [0x81, 0x39, 0xa4, 0x34, 0x302a, 20],
  [0x81, 0x39, 0xa6, 0x34, 0x303f, 2],
  [0x81, 0x39, 0xa6, 0x36, 0x3094, 7],
  [0x81, 0x39, 0xa7, 0x33, 0x309f, 2],
  [0x81, 0x39, 0xa7, 0x35, 0x30f7, 5],
  [0x81, 0x39, 0xa8, 0x30, 0x30ff, 6],
  [0x81, 0x39, 0xa8, 0x36, 0x312a, 246],
  [0x81, 0x39, 0xc1, 0x32, 0x322a, 7],
  [0x81, 0x39, 0xc1, 0x39, 0x3232, 113],
  [0x81, 0x39, 0xcd, 0x32, 0x32a4, 234],
  [0x81, 0x39, 0xe4, 0x36, 0x3390, 12],
  [0x81, 0x39, 0xe5, 0x38, 0x339f, 2],
  [0x81, 0x39, 0xe6, 0x30, 0x33a2, 34],
  [0x81, 0x39, 0xe9, 0x34, 0x33c5, 9],
  [0x81, 0x39, 0xea, 0x33, 0x33cf, 2],
  [0x81, 0x39, 0xea, 0x35, 0x33d3, 2],
  [0x81, 0x39, 0xea, 0x37, 0x33d6, 113],
  [0x81, 0x39, 0xf6, 0x30, 0x3448, 43],
  [0x81, 0x39, 0xfa, 0x33, 0x3474, 298],
  [0x82, 0x30, 0x9a, 0x31, 0x359f, 111],
  [0x82, 0x30, 0xa5, 0x32, 0x360f, 11],
  [0x82, 0x30, 0xa6, 0x33, 0x361b, 765],
  [0x82, 0x30, 0xf2, 0x38, 0x3919, 85],
  [0x82, 0x30, 0xfb, 0x33, 0x396f, 96],
  [0x82, 0x31, 0x86, 0x39, 0x39d1, 14],
  [0x82, 0x31, 0x88, 0x33, 0x39e0, 147],
  [0x82, 0x31, 0x97, 0x30, 0x3a74, 218],
  [0x82, 0x31, 0xac, 0x38, 0x3b4f, 287],
  [0x82, 0x31, 0xc9, 0x35, 0x3c6f, 113],
  [0x82, 0x31, 0xd4, 0x38, 0x3ce1, 885],
  [0x82, 0x32, 0xaf, 0x33, 0x4057, 264],
  [0x82, 0x32, 0xc9, 0x37, 0x4160, 471],
  [0x82, 0x32, 0xf8, 0x38, 0x4338, 116],
  [0x82, 0x33, 0x86, 0x34, 0x43ad, 4],
  [0x82, 0x33, 0x86, 0x38, 0x43b2, 43],
  [0x82, 0x33, 0x8b, 0x31, 0x43de, 248],
  [0x82, 0x33, 0xa3, 0x39, 0x44d7, 373],
  [0x82, 0x33, 0xc9, 0x32, 0x464d, 20],
  [0x82, 0x33, 0xcb, 0x32, 0x4662, 193],
  [0x82, 0x33, 0xde, 0x35, 0x4724, 5],
  [0x82, 0x33, 0xdf, 0x30, 0x472a, 82],
  [0x82, 0x33, 0xe7, 0x32, 0x477d, 16],
  [0x82, 0x33, 0xe8, 0x38, 0x478e, 441],
  [0x82, 0x34, 0x96, 0x39, 0x4948, 50],
  [0x82, 0x34, 0x9b, 0x39, 0x497b, 2],
  [0x82, 0x34, 0x9c, 0x31, 0x497e, 4],
  [0x82, 0x34, 0x9c, 0x35, 0x4984, 1],
  [0x82, 0x34, 0x9c, 0x36, 0x4987, 20],
  [0x82, 0x34, 0x9e, 0x36, 0x499c, 3],
  [0x82, 0x34, 0x9e, 0x39, 0x49a0, 22],
  [0x82, 0x34, 0xa1, 0x31, 0x49b8, 703],
  [0x82, 0x34, 0xe7, 0x34, 0x4c78, 39],
  [0x82, 0x34, 0xeb, 0x33, 0x4ca4, 111],
  [0x82, 0x34, 0xf6, 0x34, 0x4d1a, 148],
  [0x82, 0x35, 0x87, 0x32, 0x4daf, 81],
  [0x82, 0x35, 0x8f, 0x33, 0x9fa6, 14426],
  [0x83, 0x36, 0xc7, 0x39, 0xe76c, 1],
  [0x83, 0x36, 0xc8, 0x30, 0xe7c8, 1],
  [0x83, 0x36, 0xc8, 0x31, 0xe7e7, 13],
  [0x83, 0x36, 0xc9, 0x34, 0xe815, 1],
  [0x83, 0x36, 0xc9, 0x35, 0xe819, 5],
  [0x83, 0x36, 0xca, 0x30, 0xe81f, 7],
  [0x83, 0x36, 0xca, 0x37, 0xe827, 4],
  [0x83, 0x36, 0xcb, 0x31, 0xe82d, 4],
  [0x83, 0x36, 0xcb, 0x35, 0xe833, 8],
  [0x83, 0x36, 0xcc, 0x33, 0xe83c, 7],
  [0x83, 0x36, 0xcd, 0x30, 0xe844, 16],
  [0x83, 0x36, 0xce, 0x36, 0xe856, 14],
  [0x83, 0x36, 0xd0, 0x30, 0xe865, 4295],
  [0x84, 0x30, 0x85, 0x35, 0xf92d, 76],
  [0x84, 0x30, 0x8d, 0x31, 0xf97a, 27],
  [0x84, 0x30, 0x8f, 0x38, 0xf996, 81],
  [0x84, 0x30, 0x97, 0x39, 0xf9e8, 9],
  [0x84, 0x30, 0x98, 0x38, 0xf9f2, 26],
  [0x84, 0x30, 0x9b, 0x34, 0xfa10, 1],
  [0x84, 0x30, 0x9b, 0x35, 0xfa12, 1],
  [0x84, 0x30, 0x9b, 0x36, 0xfa15, 3],
  [0x84, 0x30, 0x9b, 0x39, 0xfa19, 6],
  [0x84, 0x30, 0x9c, 0x35, 0xfa22, 1],
  [0x84, 0x30, 0x9c, 0x36, 0xfa25, 2],
  [0x84, 0x30, 0x9c, 0x38, 0xfa2a, 1030],
  [0x84, 0x31, 0x85, 0x38, 0xfe32, 1],
  [0x84, 0x31, 0x85, 0x39, 0xfe45, 4],
  [0x84, 0x31, 0x86, 0x33, 0xfe53, 1],
  [0x84, 0x31, 0x86, 0x34, 0xfe58, 1],
  [0x84, 0x31, 0x86, 0x35, 0xfe67, 1],
  [0x84, 0x31, 0x86, 0x36, 0xfe6c, 149],
  [0x84, 0x31, 0x95, 0x35, 0xff5f, 129],
  [0x84, 0x31, 0xa2, 0x34, 0xffe6, 26],
];

// We will test 4-byte codes separately
findInvalidChars($toUnicode, $invalid, $truncated);

function notFourByteCode($gb) {
  return ((ord($gb) < 0x81 || ord($gb) > 0x84) && (ord($gb) < 0x90 || ord($gb) > 0xE3)) ||
    (strlen($gb) > 1 && (ord($gb[1]) < 0x30 || ord($gb[1]) > 0x39));
}

$invalid = array_filter($invalid, 'notFourByteCode', ARRAY_FILTER_USE_KEY);
$truncated = array_filter($truncated, 'notFourByteCode', ARRAY_FILTER_USE_KEY);

testAllValidChars($toUnicode, 'GB18030', 'UTF-16BE', false);
testAllInvalidChars($invalid, $toUnicode, 'GB18030', 'UTF-16BE', "\x00%");
testTruncatedChars($truncated, 'GB18030', 'UTF-16BE', "\x00%");

echo "Tested GB18030 (1 and 2 byte characters) -> UTF-16BE\n";

// Test one random 4-byte code for each range used for Unicode codepoints in BMP
function fourByteCodeIndex($byte4, $byte3, $byte2, $byte1) {
  return (($byte4 - 0x81) * 10 * 126 * 10) + (($byte3 - 0x30) * 10 * 126) + (($byte2 - 0x81) * 10) + ($byte1 - 0x30);
}

function fourByteCodeFromIndex($index) {
  $quotient = intdiv($index, 10 * 126 * 10);
  $byte4 = $quotient + 0x81;
  $index -= ($quotient * 10 * 126 * 10);
  $quotient = intdiv($index, 10 * 126);
  $byte3 = $quotient + 0x30;
  $index -= ($quotient * 10 * 126);
  $quotient = intdiv($index, 10);
  $byte2 = $quotient + 0x81;
  $byte1 = $index - ($quotient * 10) + 0x30;
  return chr($byte4) . chr($byte3) . chr($byte2) . chr($byte1);
}

foreach ($gb18030_BMP_Mappings as $mapping) {
  [$byte4, $byte3, $byte2, $byte1, $unicode, $n] = $mapping;
  $i = rand(0, $n-1);
  $gb = fourByteCodeFromIndex(fourByteCodeIndex($byte4, $byte3, $byte2, $byte1) + $i);
  $unicode += $i;
  testValidString($gb, pack('n', $unicode), 'GB18030', 'UTF-16BE');
}

// Invalid 4-byte codes in range for BMP
testInvalidString("\x81\x30\x81\xFF", "\x00\x00\x00%", "GB18030", "UTF-32BE");
testInvalidString("\x84\x31\xA4\x40", "\x00\x00\x00%", "GB18030", "UTF-32BE");
testInvalidString("\x84\x31\xA5\x30", "\x00\x00\x00%", "GB18030", "UTF-32BE");
testInvalidString("\x84\x32\x81\x30", "\x00\x00\x00%", "GB18030", "UTF-32BE");
testInvalidString("\x85\x31\x81\x30", "\x00\x00\x00%\x00\x00\x00%", "GB18030", "UTF-32BE");

// Valid 4-byte codes for other Unicode planes
testValidString("\x90\x30\x81\x30", "\x00\x01\x00\x00", "GB18030", "UTF-32BE");
testValidString("\xE3\x32\x9A\x35", "\x00\x10\xFF\xFF", "GB18030", "UTF-32BE");

// Invalid 4-byte codes for other Unicode planes
testInvalidString("\x90\x30\x81\xFF", "\x00\x00\x00%", "GB18030", "UTF-32BE");
testInvalidString("\xE3\x32\x9A\x36", "\x00\x00\x00%", "GB18030", "UTF-32BE");
testInvalidString("\xE4\x30\x81\x35", "\x00\x00\x00%\x00\x00\x00%", "GB18030", "UTF-32BE");

echo "Tested GB18030 4-byte characters <-> UTF-16BE\n";

testAllValidChars($fromUnicode, 'UTF-16BE', 'GB18030', false);
echo "Tested UTF-16BE -> GB18030 (1 and 2 byte characters)\n";

// Test "long" illegal character markers
mb_substitute_character("long");
convertInvalidString("\x81\x30\x81\xFF", "BAD+3081FF", "GB18030", "UTF-8");
convertInvalidString("\xE3\x32\x9A\x36", "BAD+329A36", "GB18030", "UTF-8");

echo "Done!\n";
?>
--EXPECT--
Tested GB18030 (1 and 2 byte characters) -> UTF-16BE
Tested GB18030 4-byte characters <-> UTF-16BE
Tested UTF-16BE -> GB18030 (1 and 2 byte characters)
Done!
