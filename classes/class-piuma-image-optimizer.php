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
                'piuma_img_resize_height'           => (get_option('piuma_img_resize_height')) ? get_option('piuma_img_resize_height') : 0,
                'piuma_img_resize_width'            => (get_option('piuma_img_resize_width')) ? get_option('piuma_img_resize_width') : 0,
                'piuma_img_resize_quality'          => (get_option('piuma_img_resize_quality')) ? get_option('piuma_img_resize_quality') : 100,
            );
        }

        public function __construct()
        {
            $this->set_options();

            $post_type = get_post_type();

            if (!is_admin()) {
                // add_filter('the_content', array($this, 'piuma_replace_images'), 999);
                // if (in_array($post_type, array('post','page'))) {
                //     add_filter('post_thumbnail_html', array($this, 'piuma_replace_images'), 999);
                // } 
                // else {
                //     add_filter('wp_get_attachment_url', array($this, 'piuma_replace_media_url'), 999);
                // }
                //add_filter('post_thumbnail_html', array($this, 'piuma_replace_images'), 999);



                if (in_array($post_type, array('post', 'page'))) {
                    add_filter('post_thumbnail_html', array($this, 'piuma_replace_images'), 999);
                } else
                    add_filter('wp_get_attachment_url', array($this, 'piuma_replace_media_url'), 999);

                if (!empty(get_the_content()))
                    add_filter('the_content', array($this, 'piuma_replace_images'), 999);
            }
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
            return array('jpg', 'jpeg', 'jpe', 'png');
        }


        public function piuma_url_adjust($default_attachment_url, $home_url)
        {
            $attachment_url  = $home_url;
            $attachment_url .= $this->options['piuma_img_resize_height'];
            $attachment_url .= '_';
            $attachment_url .= $this->options['piuma_img_resize_width'];
            $attachment_url .= '_';
            $attachment_url .= $this->options['piuma_img_resize_quality'];
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


            // Create an instance of DOMDocument.
            $dom = new \DOMDocument();

            // Supress errors due to malformed HTML.
            // See http://stackoverflow.com/a/17559716/3059883
            $libxml_previous_state = libxml_use_internal_errors(true);

            // Populate $dom with $content, making sure to handle UTF-8, otherwise
            // problems will occur with UTF-8 characters.
            // Also, make sure that the doctype and HTML tags are not added to our HTML fragment. http://stackoverflow.com/a/22490902/3059883
            $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            // Restore previous state of libxml_use_internal_errors() now that we're done.
            libxml_use_internal_errors($libxml_previous_state);

            // Create an instance of DOMXpath.
            $xpath = new \DOMXpath($dom);

            // Match elements with the lazy-load class (note space around class name)
            // See http://stackoverflow.com/a/26126336/3059883

            $images = $xpath->query("//img");

            // Process image HTML
            foreach ($images as $node) {
                $fallback = $node->cloneNode(true);

                $oldsrc = $node->getAttribute('src');
                //$node->setAttribute('data-src', $oldsrc);
                if ($oldsrc) {
                    $newsrc = $this->piuma_url_adjust($oldsrc, $home_url);
                    $node->setAttribute('src', $newsrc);
                }

                $oldsrcset = $node->getAttribute('srcset');
                //$node->setAttribute('data-srcset', $oldsrcset);
                if ($oldsrcset) {
                    $newsrcset = $this->piuma_url_adjust($oldsrcset, $home_url);
                    $node->setAttribute('srcset', $newsrcset);
                }

                $noscript = $dom->createElement('noscript', '');
                $node->parentNode->insertBefore($noscript, $node);
                $noscript->appendChild($fallback);
            }

            // Save and return updated HTML.
            $new_content = $dom->saveHTML();
            return  $new_content;
        }
    }
}
