<?php
namespace XFramework;

/**
 * Roundcube Plus Framework plugin.
 *
 * Copyright 2017, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once "Singleton.php";

class Html
{
    use Singleton;

    /**
     * Adds classes to the <html> element.
     *
     * @param string|array $classes
     * @param string $html
     * @return bool
     */
    public function addClassesToHtml($classes, string &$html): bool
    {
        if (empty($classes = trim(is_array($classes) ? implode(' ', $classes) : (string)$classes))) {
            return false;
        }

        $count = 0;
        $html = preg_replace(
            '/(<html\b[^>]*\bclass\s*=\s*)(["\'])([^"\']*)(\2)/i',
            '$1$2' . $classes . ' $3$4',
            $html,
            1,
            $count
        );

        if (!$count) {
            $html = preg_replace('/(<html\b)([^>]*)>/i', '$1$2 class="' . $classes . '">', $html, 1, $count);
        }

        return $count > 0;
    }

    /**
     * Adds classes to the <body> element.
     *
     * @param string|array $classes
     * @param string $html
     * @return bool
     */
    public function addClassesToBody($classes, string &$html): bool
    {
        if (empty($classes = trim(is_array($classes) ? implode(' ', $classes) : (string)$classes))) {
            return false;
        }

        $count = 0;
        $html = preg_replace(
            '/(<body\b[^>]*\bclass\s*=\s*)(["\'])([^"\']*)(\2)/i',
            '$1$2' . $classes . ' $3$4',
            $html,
            1,
            $count
        );

        if (!$count) {
            $html = preg_replace('/(<body\b)([^>]*)>/i', '$1$2 class="' . $classes . '">', $html, 1, $count);
        }

        return $count > 0;
    }

    /**
     * @param string $marker
     * @param string $insertString
     * @param string $html
     * @param string $container
     * @return bool
     */
    public function insertBefore(string $marker, string $insertString, string &$html, string $container = ""): bool
    {
        if ($pos = $this->findStart($container, $marker, $html, false)) {
            $html = substr_replace($html, $insertString, $pos, 0);
            return true;
        }

        return false;
    }

    /**
     * @param string $marker
     * @param string $tagName
     * @param string $insertString
     * @param string $html
     * @param string $container
     * @return bool
     */
    public function insertAfter(string $marker, string $tagName, string $insertString, string &$html, string $container = ""): bool
    {
        if ($pos = $this->findEnd($container, $marker, $tagName, $html)) {
            $html = substr_replace($html, $insertString, $pos, 0);
            return true;
        }

        return false;
    }

    /**
     * @param string $marker
     * @param string $insertString
     * @param string $html
     * @param string $container
     * @return bool
     */
    public function insertAtBeginning(string $marker, string $insertString, string &$html, string $container = ""): bool
    {
        if ($pos = $this->findStart($container, $marker, $html, true)) {
            $html = substr_replace($html, $insertString, $pos, 0);
            return true;
        }

        return false;
    }

    /**
     * @param string $marker - String to search for, it can be a class or id within a tag or a text within a tag. The function
     *        will search for the first tag to the left of the marker to identify the element at the end of which the
     *        text should be inserted.
     * @param string $insertString - String to insert before the closing tag.
     * @param string $html - Html code to modify.
     * @return bool - True if the string has been successfully inserted, false otherwise.
     */
    public function insertAtEnd(string $marker, string $insertString, string &$html): bool
    {
        // find marker
        if (!($i = stripos($html, $marker))) {
            return false;
        }

        // get the html element
        if (!($i = strripos(substr($html, 0, $i), "<")) || !($j = stripos($html, " ", $i))) {
            return false;
        }

        $tag = substr($html, $i + 1, $j - $i - 1);
        $count = 0;

        do {
            if (($c = stripos($html, "</$tag>", $i)) === false) {
                return false;
            }

            if (($n = stripos($html, "<$tag ", $i)) === false) {
                $n = $c + 1;
            }

            if ($c > $n) {
                $count++;
                $i = $n + 1;
            } else {
                $count--;
                $i = $c + 1;
            }
        } while ($count);

        $html = substr_replace($html, $insertString, $i - 1, 0);

        return true;
    }

    /**
     * @param string $insertString
     * @param string $html
     * WARNING: Don't use this to insert html because it causes Roundcube to re-order script tag positioning and some plugins
     * that insert their code at the end of the page might not get the scripts they expect (for example, Thunderbird labels.)
     */
    public function insertBeforeBodyEnd(string $insertString, string &$html)
    {
        $html = str_replace("</body>", $insertString . "</body>", $html);
    }

    /**
     * @param string $insertString
     * @param string $html
     * @return bool
     */
    public function insertAfterBodyStart(string $insertString, string &$html): bool
    {
        if (($i = strpos($html, "<body ")) !== false &&
            ($j = strpos($html, ">", $i + 1))
        ) {
            $html = substr_replace($html, "\n" . $insertString, $j + 1, 0);
            return true;
        }

        return false;
    }

    /**
     * @param string $insertString
     * @param string $html
     */
    public function insertBeforeHeadEnd(string $insertString, string &$html)
    {
        $html = str_replace("</head>", $insertString . "</head>", $html);
    }

    /**
     * @param string $container
     * @param string $marker
     * @param string $html
     * @param bool $inner
     * @return bool|int
     */
    private function findStart(string $container, string $marker, string $html, bool $inner)
    {
        if (!($pos = $this->findMarker($container, $marker, $html))) {
            return false;
        }

        if ($inner) {
            if (substr($marker, -1, 1) != ">") {
                $pos = strpos($html, ">", $pos);
                if ($pos) {
                    $pos++;
                }
            }
        } else {
            // if marker doesn't include the opening tag name, find the beginning of the tag
            if (strpos($marker, "<") !== 0) {
                $pos = strrpos(substr($html, 0, $pos + 1), "<");
            }
        }

        return $pos;
    }

    /**
     * @param string $container
     * @param string $marker
     * @param string $tagName
     * @param string $html
     * @return bool|int
     */
    private function findEnd(string $container, string $marker, string $tagName, string $html)
    {
        if (!($pos = $this->findMarker($container, $marker, $html))) {
            return false;
        }

        // find the closing tag
        $end = $pos;

        do {
            $innerTagStart = strpos($html, "<$tagName ", $end + 1);
            $end = strpos($html, "</$tagName>", $end + 1);
        } while ($end !== false && $innerTagStart !== false && $innerTagStart < $end);

        return $end + strlen("</$tagName>");
    }

    /**
     * @param string $container
     * @param string $marker
     * @param string $html
     * @return bool|int
     */
    private function findMarker(string $container, string $marker, string $html)
    {
        $start = empty($container) ? strpos($html, "<body ") : strpos($html, $container);

        if ($start === false) {
            return false;
        }

        return strpos($html, $marker, $start);
    }
}