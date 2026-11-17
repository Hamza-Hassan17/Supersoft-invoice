<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Minimal QR code generator written for this project (no outside code).
 *
 * Supports byte mode, error correction level M, versions 1-10 (up to 213
 * bytes), which is plenty for an invoice link. Follows ISO/IEC 18004.
 */
class Qr_code
{
    // Per version (1-10), level M: [total codewords, EC codewords per block, number of blocks]
    private static $versions = array(
        1 => array(26, 10, 1), 2 => array(44, 16, 1), 3 => array(70, 26, 1),
        4 => array(100, 18, 2), 5 => array(134, 24, 2), 6 => array(172, 16, 4),
        7 => array(196, 18, 4), 8 => array(242, 22, 4), 9 => array(292, 22, 5),
        10 => array(346, 26, 5),
    );

    private static $alignment = array(
        1 => array(), 2 => array(6, 18), 3 => array(6, 22), 4 => array(6, 26), 5 => array(6, 30),
        6 => array(6, 34), 7 => array(6, 22, 38), 8 => array(6, 24, 42), 9 => array(6, 26, 46),
        10 => array(6, 28, 50),
    );

    private $size;
    private $modules = array();
    private $function = array();

    /**
     * Returns the QR matrix as rows of booleans (TRUE = dark), or NULL if the
     * text is too long. $mask forces a mask pattern (0-7); used by tests.
     */
    public static function matrix($text, $mask = NULL)
    {
        $data = array_values(unpack('C*', (string)$text) ?: array());

        foreach (self::$versions as $version => $info) {
            $capacity = ($info[0] - $info[1] * $info[2]) * 8;
            $count_bits = $version < 10 ? 8 : 16;
            if (4 + $count_bits + count($data) * 8 <= $capacity) {
                $qr = new self();
                return $qr->build($version, $data, $mask);
            }
        }

        return NULL;
    }

    private function build($version, $data, $forced_mask)
    {
        $this->size = $version * 4 + 17;
        $this->modules = array_fill(0, $this->size, array_fill(0, $this->size, FALSE));
        $this->function = $this->modules;

        $this->draw_function_patterns($version);
        $this->draw_codewords($this->add_error_correction($version, $this->encode($version, $data)));

        if ($forced_mask !== NULL) {
            $mask = $forced_mask;
        } else {
            $mask = 0;
            $best = PHP_INT_MAX;
            for ($m = 0; $m < 8; $m++) {
                $this->apply_mask($m);
                $this->draw_format_bits($m);
                $penalty = $this->penalty();
                if ($penalty < $best) {
                    $best = $penalty;
                    $mask = $m;
                }
                $this->apply_mask($m); // XOR again to undo
            }
        }

        $this->apply_mask($mask);
        $this->draw_format_bits($mask);

        return $this->modules;
    }

    private function encode($version, $data)
    {
        $info = self::$versions[$version];
        $capacity = ($info[0] - $info[1] * $info[2]) * 8;

        $bits = array();
        $this->append_bits($bits, 4, 4); // byte mode 0100
        $this->append_bits($bits, count($data), $version < 10 ? 8 : 16);
        foreach ($data as $byte) {
            $this->append_bits($bits, $byte, 8);
        }

        $this->append_bits($bits, 0, min(4, $capacity - count($bits))); // terminator
        $this->append_bits($bits, 0, (8 - count($bits) % 8) % 8);

        $bytes = array();
        for ($i = 0; $i < count($bits); $i += 8) {
            $byte = 0;
            for ($j = 0; $j < 8; $j++) {
                $byte = ($byte << 1) | $bits[$i + $j];
            }
            $bytes[] = $byte;
        }

        for ($pad = 0xEC; count($bytes) < $capacity / 8; $pad ^= 0xEC ^ 0x11) {
            $bytes[] = $pad;
        }

        return $bytes;
    }

    private function append_bits(&$bits, $value, $length)
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    private function add_error_correction($version, $data)
    {
        list($raw, $ec_len, $num_blocks) = self::$versions[$version];
        $num_short = $num_blocks - $raw % $num_blocks;
        $short_len = intdiv($raw, $num_blocks);
        $divisor = self::rs_divisor($ec_len);

        $blocks = array();
        for ($i = 0, $k = 0; $i < $num_blocks; $i++) {
            $data_len = $short_len - $ec_len + ($i < $num_short ? 0 : 1);
            $block = array_slice($data, $k, $data_len);
            $k += $data_len;
            $ec = self::rs_remainder($block, $divisor);
            if ($i < $num_short) {
                $block[] = NULL; // placeholder so all blocks line up
            }
            $blocks[] = array_merge($block, $ec);
        }

        $result = array();
        for ($i = 0; $i < count($blocks[0]); $i++) {
            foreach ($blocks as $block) {
                if ($block[$i] !== NULL) {
                    $result[] = $block[$i];
                }
            }
        }

        return $result;
    }

    private static function gf_multiply($x, $y)
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }
        return $z;
    }

    private static function rs_divisor($degree)
    {
        $result = array_fill(0, $degree, 0);
        $result[$degree - 1] = 1;
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = self::gf_multiply($result[$j], $root);
                if ($j + 1 < $degree) {
                    $result[$j] ^= $result[$j + 1];
                }
            }
            $root = self::gf_multiply($root, 0x02);
        }
        return $result;
    }

    private static function rs_remainder($data, $divisor)
    {
        $result = array_fill(0, count($divisor), 0);
        foreach ($data as $byte) {
            $factor = $byte ^ array_shift($result);
            $result[] = 0;
            foreach ($divisor as $i => $coefficient) {
                $result[$i] ^= self::gf_multiply($coefficient, $factor);
            }
        }
        return $result;
    }

    private function set_function($x, $y, $dark)
    {
        $this->modules[$y][$x] = $dark;
        $this->function[$y][$x] = TRUE;
    }

    private function draw_function_patterns($version)
    {
        for ($i = 0; $i < $this->size; $i++) {
            $this->set_function(6, $i, $i % 2 == 0);
            $this->set_function($i, 6, $i % 2 == 0);
        }

        $this->draw_finder(3, 3);
        $this->draw_finder($this->size - 4, 3);
        $this->draw_finder(3, $this->size - 4);

        $positions = self::$alignment[$version];
        $count = count($positions);
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                $corner = ($i == 0 && $j == 0) || ($i == 0 && $j == $count - 1) || ($i == $count - 1 && $j == 0);
                if (!$corner) {
                    $this->draw_alignment($positions[$i], $positions[$j]);
                }
            }
        }

        $this->draw_format_bits(0); // reserve the area; redrawn after masking
        $this->draw_version($version);
    }

    private function draw_finder($cx, $cy)
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $x = $cx + $dx;
                $y = $cy + $dy;
                if ($x >= 0 && $x < $this->size && $y >= 0 && $y < $this->size) {
                    $distance = max(abs($dx), abs($dy));
                    $this->set_function($x, $y, $distance != 2 && $distance != 4);
                }
            }
        }
    }

    private function draw_alignment($cx, $cy)
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $this->set_function($cx + $dx, $cy + $dy, max(abs($dx), abs($dy)) != 1);
            }
        }
    }

    private function draw_format_bits($mask)
    {
        $data = (0 << 3) | $mask; // level M = 00
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;
        $bit = function ($i) use ($bits) {
            return (($bits >> $i) & 1) == 1;
        };

        for ($i = 0; $i <= 5; $i++) {
            $this->set_function(8, $i, $bit($i));
        }
        $this->set_function(8, 7, $bit(6));
        $this->set_function(8, 8, $bit(7));
        $this->set_function(7, 8, $bit(8));
        for ($i = 9; $i < 15; $i++) {
            $this->set_function(14 - $i, 8, $bit($i));
        }

        for ($i = 0; $i < 8; $i++) {
            $this->set_function($this->size - 1 - $i, 8, $bit($i));
        }
        for ($i = 8; $i < 15; $i++) {
            $this->set_function(8, $this->size - 15 + $i, $bit($i));
        }
        $this->set_function(8, $this->size - 8, TRUE); // always-dark module
    }

    private function draw_version($version)
    {
        if ($version < 7) {
            return;
        }

        $rem = $version;
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
        }
        $bits = ($version << 12) | $rem;

        for ($i = 0; $i < 18; $i++) {
            $dark = (($bits >> $i) & 1) == 1;
            $a = $this->size - 11 + $i % 3;
            $b = intdiv($i, 3);
            $this->set_function($a, $b, $dark);
            $this->set_function($b, $a, $dark);
        }
    }

    private function draw_codewords($codewords)
    {
        $total = count($codewords) * 8;
        $i = 0;
        for ($right = $this->size - 1; $right >= 1; $right -= 2) {
            if ($right == 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $this->size; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) == 0;
                    $y = $upward ? $this->size - 1 - $vert : $vert;
                    if (!$this->function[$y][$x] && $i < $total) {
                        $this->modules[$y][$x] = (($codewords[$i >> 3] >> (7 - ($i & 7))) & 1) == 1;
                        $i++;
                    }
                }
            }
        }
    }

    private function apply_mask($mask)
    {
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                switch ($mask) {
                    case 0: $invert = ($x + $y) % 2 == 0; break;
                    case 1: $invert = $y % 2 == 0; break;
                    case 2: $invert = $x % 3 == 0; break;
                    case 3: $invert = ($x + $y) % 3 == 0; break;
                    case 4: $invert = (intdiv($x, 3) + intdiv($y, 2)) % 2 == 0; break;
                    case 5: $invert = $x * $y % 2 + $x * $y % 3 == 0; break;
                    case 6: $invert = ($x * $y % 2 + $x * $y % 3) % 2 == 0; break;
                    default: $invert = (($x + $y) % 2 + $x * $y % 3) % 2 == 0;
                }
                if ($invert && !$this->function[$y][$x]) {
                    $this->modules[$y][$x] = !$this->modules[$y][$x];
                }
            }
        }
    }

    private function penalty()
    {
        $size = $this->size;
        $penalty = 0;
        $lines = array();

        for ($y = 0; $y < $size; $y++) {
            $row = '';
            $column = '';
            for ($x = 0; $x < $size; $x++) {
                $row .= $this->modules[$y][$x] ? '1' : '0';
                $column .= $this->modules[$x][$y] ? '1' : '0';
            }
            $lines[] = $row;
            $lines[] = $column;
        }

        foreach ($lines as $line) {
            // Runs of 5 or more same-colour modules
            preg_match_all('/0{5,}|1{5,}/', $line, $runs);
            foreach ($runs[0] as $run) {
                $penalty += 3 + strlen($run) - 5;
            }
            // Finder-like patterns
            $penalty += 40 * (substr_count($line, '10111010000') + substr_count($line, '00001011101'));
        }

        $dark = 0;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $dark += $this->modules[$y][$x] ? 1 : 0;
                if ($x < $size - 1 && $y < $size - 1) {
                    $colour = $this->modules[$y][$x];
                    if ($colour == $this->modules[$y][$x + 1] && $colour == $this->modules[$y + 1][$x]
                        && $colour == $this->modules[$y + 1][$x + 1]) {
                        $penalty += 3;
                    }
                }
            }
        }

        $total = $size * $size;
        $penalty += (int)(ceil(abs($dark * 20 - $total * 10) / $total) - 1) * 10;

        return $penalty;
    }
}

/**
 * HTML for a QR code in a PDF, $mm millimetres wide. Uses a PNG drawn with
 * PHP's built-in GD when available, otherwise a plain HTML table.
 */
function qr_code_html($text, $mm = 24)
{
    $matrix = Qr_code::matrix($text);
    if (!$matrix) {
        return '';
    }

    $size = count($matrix);
    $quiet = 2;

    if (function_exists('imagecreate') && function_exists('imagepng')) {
        $scale = 6;
        $pixels = ($size + $quiet * 2) * $scale;
        $image = imagecreate($pixels, $pixels);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);
        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $dark) {
                if ($dark) {
                    $left = ($x + $quiet) * $scale;
                    $top = ($y + $quiet) * $scale;
                    imagefilledrectangle($image, $left, $top, $left + $scale - 1, $top + $scale - 1, $black);
                }
            }
        }

        $dir = FCPATH . 'uploads/temp/';
        $file = $dir . 'qr_' . md5($text) . '.png';
        if (is_dir($dir) && imagepng($image, $file)) {
            imagedestroy($image);
            return '<img src="' . $file . '" style="width:' . $mm . 'mm;height:' . $mm . 'mm;">';
        }
        imagedestroy($image);
    }

    $cell = round($mm / ($size + $quiet * 2), 3);
    $html = '<table style="border-collapse:collapse;margin:' . ($cell * $quiet) . 'mm 0;">';
    foreach ($matrix as $row) {
        $html .= '<tr>';
        foreach ($row as $dark) {
            $html .= '<td style="width:' . $cell . 'mm;height:' . $cell . 'mm;padding:0;background:' . ($dark ? '#000' : '#fff') . ';"></td>';
        }
        $html .= '</tr>';
    }

    return $html . '</table>';
}
