<?php
defined('ABSPATH') or die('');


if (!class_exists('piumaImageOptimizer')) {
    class piumaImageOptimizer
    {
        private $options;


        public function set_options()
        {
            $this->options = array(
                'piuma_base_remote_url'             => (get_option('piuma_base_remote_url') ? get_option('piuma_base_remote_url') : PIO_DIRECTORY_URL),
                //'piuma_img_resize_height'           => (get_option('piuma_img_resize_height')) ? get_option('piuma_img_resize_height') : 0,
                //'piuma_img_resize_width'            => (get_option('piuma_img_resize_width')) ? get_option('piuma_img_resize_width') : 0,
                'piuma_img_convert'                 => (get_option('piuma_img_convert')) ? get_option('piuma_img_convert') : '',
                'piuma_img_resize_height'           => 0,
                'piuma_img_resize_width'            => 0,
                'piuma_img_resize_quality'          => (get_option('piuma_img_resize_quality')) ? get_option('piuma_img_resize_quality') : 100,
                'piuma_img_resize_quality_adaptive' => get_option('piuma_img_resize_quality_adaptive') ?: false
            );
        }


        public function piuma_find_file($pattern, $flags = 0)
        {
            $files = glob($pattern, $flags);
            foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
                $files = array_merge($files, $this->piuma_find_file($dir . '/' . basename($pattern), $flags));
            }
            return $files;
        }

        public function piuma_get_allowed_extensions()
        {
            return array('jpg', 'jpeg', 'jpe', 'png', 'webp');
        }

        public function piuma_url_adjust($default_attachment_url, $home_url)
        {
            $attachment_url  = $home_url;
            $attachment_url .= $this->options['piuma_img_resize_height'];
            $attachment_url .= '_';
            $attachment_url .= $this->options['piuma_img_resize_width'];
            $attachment_url .= '_';
            $attachment_url .= $this->options['piuma_img_resize_quality'];
            if ($this->options['piuma_img_resize_quality_adaptive']) {
              $attachment_url .= 'a';
            }
            if (!empty($this->options['piuma_img_convert'])) {
                $attachment_url .= ':' . $this->options['piuma_img_convert'];
            }
            $attachment_url .= '/';
            $attachment_url .= $default_attachment_url;

            return $attachment_url;
        }

        public function piuma_replace_media_url($attachment_url)
        {
            $mime_types_array = $this->piuma_get_allowed_extensions();
            $extension  = pathinfo($attachment_url, PATHINFO_EXTENSION);
            $default_attachment_url = $attachment_url;

            // Only the selected file extension should be rewritten
            if (in_array($extension, $mime_types_array)) {
                //$home_url = preg_quote( rtrim( get_home_url(), "/"), "/" );
                //$attachment_url = preg_replace("/(?!{$home_url})(wp-content\/uploads\/)/ui", PIO_MEDIA_DIR, $attachment_url);

                $home_url = $this->options['piuma_base_remote_url'];

                $attachment_url = $this->piuma_url_adjust($default_attachment_url, $home_url);
            }
            return $attachment_url;
        }

        public function piuma_replace_images($content)
        {

            $home_url = $this->options['piuma_base_remote_url'];
            $image_attributes = array('src');

            // Create an instance of DOMDocument.
            $dom = new \DOMDocument();

            // Supress errors due to malformed HTML.
            // See http://stackoverflow.com/a/17559716/3059883
            $libxml_previous_state = libxml_use_internal_errors(true);

            // Populate $dom with $content, making sure to handle UTF-8, otherwise
            // problems will occur with UTF-8 characters.
            // Also, make sure that the doctype and HTML tags are not added to our HTML fragment. http://stackoverflow.com/a/22490902/3059883
            $dom->loadHTML('<div>' . mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8') . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            // Restore previous state of libxml_use_internal_errors() now that we're done.
            libxml_use_internal_errors($libxml_previous_state);

            // Create an instance of DOMXpath.
            $xpath = new \DOMXpath($dom);

            // Match elements with the lazy-load class (note space around class name)
            // See http://stackoverflow.com/a/26126336/3059883

            $images = $xpath->query("//img");

            foreach ($images as $node) {

                foreach ($image_attributes as $image_attribute) {
                    $fallback = $node->cloneNode(true);

                    $current_img_attr = $node->getAttribute($image_attribute);
                    $extension = pathinfo($current_img_attr, PATHINFO_EXTENSION);

                    if ($current_img_attr && in_array($extension, $this->piuma_get_allowed_extensions())) {
                        $new_img_attr = $this->piuma_url_adjust($current_img_attr, $home_url);
                        $node->setAttribute($image_attribute, $new_img_attr);
                    }
                }

                $noscript = $dom->createElement('noscript', '');
                $node->parentNode->insertBefore($noscript, $node);
                $noscript->appendChild($fallback);
            }

            // Save and return updated HTML.
            $new_content = $dom->saveHTML();
            $new_content = substr($new_content, 5, -7);

            return $new_content;
        }

        function srcset_replace($sources)
        {
            //wordpress-srcset-cdn.php
            $piuma_base_remote_url = $this->options['piuma_base_remote_url'];
            $wp_home_url = get_home_url();

            foreach ($sources as $source) {
                $source_url_converted = $this->piuma_url_adjust($wp_home_url, $piuma_base_remote_url);
                $sources[$source['value']]['url'] = str_replace($wp_home_url, $source_url_converted, $sources[$source['value']]['url']);
            }
            return $sources;
        }

        function autoptimize_replace($img_src)
        {
            $parsed_site_url = parse_url(site_url());

            if (autoptimizeUtils::is_protocol_relative($img_src)) {
                $img_src = $parsed_site_url['scheme'] . ':' . $img_src;
            }

            return $this->filter_replace($img_src);
        }

        function filter_replace($img_src)
        {
            $mime_types_array = $this->piuma_get_allowed_extensions();
            $extension = pathinfo($img_src, PATHINFO_EXTENSION);

            if (in_array($extension, $mime_types_array)) {
                $home_url = $this->options['piuma_base_remote_url'];
                $img_src = $this->piuma_url_adjust($img_src, $home_url);
            }

            return $img_src;
        }

        public function __construct()
        {
            $this->set_options();
            $post_id = get_the_ID();

            if (!is_admin() || wp_doing_ajax()) {

                add_action('wp_body_open', function () {
                    add_filter('wp_get_attachment_url', array($this, 'piuma_replace_media_url'), 999);
                });
                add_filter('the_content', array($this, 'piuma_replace_images'), 999);
                add_filter('wp_calculate_image_srcset', array($this, 'srcset_replace'), 999);

                // Autoptimize integration
                add_filter('autoptimize_filter_base_replace_cdn', array($this, 'autoptimize_replace'), 999, 1);

                // Custom filter for template images
                add_filter('piuma_image_src', array($this, 'filter_replace'), 10, 1);
            }
        }
    }
}
