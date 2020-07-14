<?php

/**
 * MIT License <https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE>
 * Copyright (c) 2018 Arman Afzal <rman.afzal@gmail.com>
 */

if (!class_exists('WP_CI_Calendar_Heatmap')) {

    class WP_CI_Calendar_Heatmap
    {

        public $data = [];
        private $dowmap = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        /**
         * @since 2.2.6
         */
        public function load($table_name, $id, $start, $end){

            global $wpdb;

            // get post title
            $post = get_post( $id );
            $id = $post->post_title;

            // convert dates to mysql format
            $start = $start->format('Y-m-d H:i:s');
            $end   = $end->format('Y-m-d H:i:s');


            $this->data = $wpdb->get_results(
                "SELECT time, WEEKDAY(time) weekday, HOUR(time) hour,
                COUNT(DISTINCT ip) unique_hits,
                COUNT(*) total_hits,
                SUM(case when error = '' then 0 else 1 end) total_errors
                FROM $table_name
                WHERE code='$id' AND (time BETWEEN '$start' AND '$end')
                GROUP BY hour"
            , ARRAY_A );

        }

        /**
         * @since 2.2.6
         */
        function render(){

            $max = 10;

            if(count($this->data) > 0)
            {
                $max = max(array_map(function($item){   
                    return intval($item["unique_hits"]);
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

                        $hits = intval($data[0]["unique_hits"]);
                        
                        $plural = $hits > 1;

                        $color = self::get_color($hits , $max);

                        $time = date_i18n("M j, H:i", strtotime($data[0]["time"]));

                        ?>

                        <div class="gdcp-heatmap-cell" style="background-color: <?php echo $color; ?>;">
                            <p class="info">
                                <span><strong><?php echo $hits . ($plural ? " hits - " : " hit - "); ?></strong><span><span class="time"><?php echo $time; ?><span>
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

            $result = "<span class=\"gdcp-chart-colors\"><i>Less</i>";
            
            foreach(range(0,9) as $i){

                $color = self::get_color($i , 9);

                $result = $result . "<i class=\"gradient\" style=\"background-color: $color;\" ></i>";

            }
            
            $result = $result . "<i>More</i></span>";

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
 