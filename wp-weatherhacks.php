<?php
/*
Plugin Name: WP Weather Hacks
Plugin URI: http://firegoby.theta.ne.jp/wp/weatherhacks
Description: ライブドアのWeather Hacksを利用して天気予報を表示するサイドバーウィジェット
Author: Takayuki Miyauchi (THETA NETWORKS Co,.Ltd)
Version: 0.1
Author URI: http://firegoby.theta.ne.jp/
*/

require_once(dirname(__FILE__).'/includes/weatherHacks.class.php');
require_once(dirname(__FILE__).'/includes/tinyTemplate.php');

class WeatherHacksWidget extends WP_Widget {

    private $forecastmap = 'http://weather.livedoor.com/forecast/rss/forecastmap.xml';
    private $template = 'widget.html';

    function __construct() {
        parent::__construct(false, $name = '天気予報');
    }

    public function form($instance) {
        // outputs the options form on admin
        $cityID = esc_attr($instance['city']);
        $pfield = $this->get_field_id('city');
        $pfname = $this->get_field_name('city');
        echo 'タイトル:';
        echo '<p>';
        echo sprintf(
            '<input class="widefat" type="text" id="%s" name="%s" value="%s">',
            $this->get_field_id('title'),
            $this->get_field_name('title'),
            esc_attr($instance['title'])
        );
        echo '</p>';
        echo "どこの都市の天気予報を表示しますか？";
        echo '<p>';
        echo "<select class=\"widefat\" id=\"{$pfield}\" name=\"{$pfname}\">";
        echo "<option value=\"\">選択してください。</option>";
        $dom = new DOMDocument();
        if (@$dom->load($this->forecastmap)) {
            $cities = $dom->getElementsByTagName('city');
            foreach ($cities as $city) {
                $id    = $city->getAttribute('id');
                $title = $city->getAttribute('title');
                if ($cityID == $id) {
                    echo "<option value=\"{$id}\" selected=\"selected\">{$title}</option>";
                } else {
                    echo "<option value=\"{$id}\">{$title}</option>";
                }
            }
        }
        echo "</select>";
        echo '</p>';
    }

    public function update($new_instance, $old_instance) {
        // processes widget options to be saved
        return $new_instance;
    }

    public function widget($args, $instance) {
        extract($args);
        $wh = new weatherHacks($instance['city']);
	    $upload = wp_upload_dir();
        $wh->setCache($upload['basedir'], 3600);
        $w = $wh->getArray();
        if ($w) {
            echo $before_widget;
            echo $before_title . $instance['title'] . $after_title;
            echo "<div class=\"weather-block\">";
            $i = 0;
            $title = array(
                '今日',
                '明日',
                'あさって',
            );
            foreach ($w as $d) {
                $tpl = new TinyTemplate();
                $tpl->set('title', $title[$i]);
                $tpl->set('img', $d['img']);
                $tpl->set('width', $d['width']);
                $tpl->set('height', $d['height']);
                $tpl->set('weather', $d['weather']);
                if ($d['max']) {
                    $tpl->set('max', $d['max']);
                } else {
                    $tpl->set('max', '-');
                }
                if ($d['min']) {
                    $tpl->set('min', $d['min']);
                } else {
                    $tpl->set('min', '-');
                }
                echo $tpl->fetch(dirname(__FILE__).'/'.$this->template);
                $i++;
            }
            echo '<br clear="all" />';
            echo "</div>";
            echo $after_widget;
        }
    }

}

function weatherHacksLoadCSS(){
    $url = WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__));
    echo $url;
}

add_action(
    'widgets_init',
    create_function('', 'return register_widget("WeatherHacksWidget");')
);

add_action(
    'wp_head',
    function(){
        $url = WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__));
        $css = $url.'/style.css';
        echo '<link rel="stylesheet" type="text/css" media="all" href="'.$css.'">';
    }
);

?>
