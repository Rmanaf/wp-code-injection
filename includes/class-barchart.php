<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

class Barchart
{


    private $data = [];

    private $width = 0;

    private $height = 0;

    private $gap = 1;

    function __construct($data, $w, $h, $g = 1)
    {
        $this->data = $data;
        $this->width = $w;
        $this->height = $h;
        $this->gap = $g;
    }


    /**
     * @since 2.4.5
     */
    function render()
    {

        $max = 0;

        $index = 0;

        if (count($this->data) > 0) {
            $max = max(array_map(function ($item) {
                return intval($item["value"]);
            }, $this->data));
        }

        $yScale = $max / $this->height;

        $length = count($this->data);

        $barWidth = ($this->width / $length) - $this->gap;

        $svgHeight = $this->height + 25;


        echo "<svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" " .
            "xmlns:xlink=\"http://www.w3.org/1999/xlink\" " .
            "class=\"chart\" width=\"$this->width\" height=\"$svgHeight\" " .
            "aria-labelledby=\"title\" role=\"img\">";


        foreach ([0, $this->height / 2, $this->height] as $y) {
            echo "<g>" .
                "<line x1=\"0\" y1=\"$y\" x2=\"$this->width\" y2=\"$y\" stroke=\"#eee\" />" .
                "</g>";
        }


        foreach ($this->data as $d) {

            $num        = intval(isset($d['value']) ? $d['value'] : 0);
            $tooltip    = isset($d['index']) ? $d['index'] : '';

            if ($yScale > 0) {
                $height = $num / $yScale;
            } else {
                $height = $num;
            }

            $x = ($index * ($barWidth + $this->gap));

            $y = $this->height - $height;

            $lineX = ($barWidth / 2) + $x;

            $tooltipY = $this->height + 12;

            $tooltipX = min([max([$lineX, 9]), $this->width - 9]);


            if (!empty($tooltip)) {

                $tooltip = "<g class=\"tooltip\" style=\"transform:translate({$tooltipX}px,{$tooltipY}px);\">" .
                    "<circle class=\"bg\" r=\"9\" fill=\"#333\" />" .
                    "<text dy=\".35em\" x=\"0\" y=\"0\" text-anchor=\"middle\" class=\"count\" fill=\"#fff\">$tooltip</text>" .
                    "</g>";
            }

            echo "<g class=\"bar\">" .
                "<line class=\"grid\" x1=\"$lineX\" y1=\"0\" x2=\"$lineX\" y2=\"$this->height\" stroke-width=\"4\" />" .
                "<rect y=\"$y\" x=\"$x\" width=\"$barWidth\" height=\"$height\" />" .
                $tooltip .
                "</g>";

            $index++;
        }

        echo "</svg>";
    }
}
