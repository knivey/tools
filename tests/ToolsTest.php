<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ToolsTest extends TestCase
{
    public function testIsDigit()
    {
        for($i = 0; $i < 20; $i++) {
            $this->assertTrue(knivey\tools\isDigit((string)$i), "Failed on $i");
        }
        $this->assertFalse(knivey\tools\isDigit("a"));
        $this->assertFalse(knivey\tools\isDigit("/"));
        $this->assertFalse(knivey\tools\isDigit(":"));
    }

    public function escapeRegexProvider()
    {
        return
        [
            ["arst", "arst"],
            ["ar\st", "ar\st"],
            ["ar\\\\st", "ar\\\\\\st"],
            ["ar\\0st", "ar\\\\0st"],
            ["ar\\0st\\1", "ar\\\\0st\\\\1"],
            ["ar$0st", "ar\\$0st"],
            ["ar$0st$1", "ar\\$0st\\$1"],
        ];
    }

    /**
     * @dataProvider escapeRegexProvider
     */
    public function testEscapeRegexReplace($input, $expected): void
    {
        $this->assertSame($expected, knivey\tools\escapeRegexReplace($input));
    }

    public function globToRegexProvider()
    {
        return
            [
                ["arst", "/^arst$/"],
                ["ar*st", "/^ar.*st$/"],
                ["*ar*st", "/^.*ar.*st$/"],
                ["ar.st", "/^ar\\.st$/"],
                ["ar\\st?1", "/^ar\\\\st.1$/"],
                ["ar\\s/t?1", "/^ar\\\\s\\/t.1$/"],
            ];
    }

    /**
     * @dataProvider globToRegexProvider
     */
    public function testGlobToRegex($input, $expected): void
    {
        $this->assertSame($expected, knivey\tools\globToRegex($input));
    }

    public function testGlobToRegexDelimiterAndAnchor(): void
    {
        $this->assertSame("/ar.*s\\/t/", knivey\tools\globToRegex("ar*s/t", anchor: false));
        $this->assertSame("@ar.*s/t@", knivey\tools\globToRegex("ar*s/t", delimiter: "@", anchor: false));
    }
}