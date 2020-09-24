<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Arman Afzal (https://rmanaf.com)
 */

if (!class_exists('WP_CI_Calendar_Heatmap')) {

    class WP_CI_Calendar_Heatmap
    {

        private $data = [];

        private $dowmap = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        function __construct($data){
            
            $this->data = $data;

        }
        
        /**
         * @since 2.2.6
         */
        function render(){

            $max = 10;

            if(count($this->data) > 0)
            {
                $max = max(array_map(function($item){   
                    return intval($item["total_hits"]);
                }, $this->data )); 
            }

            if($max < 10)
            {
                $max = 10;
            }

            echo "<div class=\"gdcp-heatmap-container\">";

            foreach(range(0,6) as $weekday)
            {

                echo "<div class=\"gdcp-heatmap-row\">";

                if($weekday%2 != 0){
                    echo "<span class=\"dow\">{$this->dowmap[$weekday]}</span>";
                } else {
                    echo "<span class=\"dow\">&nbsp;</span>";
                }


                foreach(range(0,24) as $hour)
                {

                    $data = array_values(array_filter($this->data , function($dt) use ($weekday , $hour) {
                        return $dt['weekday'] == $weekday && $dt['hour'] == $hour;
                    }));
                
                    if(!empty($data)){

                        $hits = intval($data[0]["total_hits"]);
                        
                        $plural = $hits > 1;

                        $color = self::get_color($hits , $max);

                        $time = date_i18n("M j, H:i", strtotime($data[0]["time"]));

                        ?>

                        <div class="gdcp-heatmap-cell" style="background-color: <?php echo $color; ?>;">
                            <p class="info">
                                <strong><?php echo $hits . ($plural ? " hits - " : " hit - "); ?></strong><span><?php echo $time; ?><span>
                                <i class="arrow-down"></i>
                            </p>
                        </div>

                        <?php

                    }else{

                        echo "<div class='gdcp-heatmap-cell'></div>";

                    }

                }

                echo "</div>";

            }

            echo "</div>";

        }


        /**
         * @since 2.2.6
         */
        static function map(){

            $result = "<span class=\"gdcp-chart-colors\">";
            
            foreach(range(0,9) as $i){

                $color = self::get_color($i , 9);

                $result = $result . "<i class=\"gradient\" style=\"background-color: $color;\" ></i>";

            }
            
            $result = $result . "</span>";

            return $result;

        }

        /**
         * @since 2.2.6
         */
        static function get_color($value , $max){

            $h = (1.0 - ($value / $max)) * 240;
            
            return "hsl(" . $h . ", 100%, 50%)";  
        }

    }

}
 