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
            # Detect right files 
            //add_filter('request', array($this, 'piuma_detect_image'), 999);

            $post_type = get_post_type();

            add_filter('the_content', array($this, 'piuma_replace_images'), 999);
            if (in_array($post_type, array('post', 'page'))) {

                add_filter('post_thumbnail_html', array($this, 'piuma_replace_images'), 999);
            } else {
                add_filter('wp_get_attachment_url', array($this, 'piuma_replace_media_url'), 999);
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


        public function piuma_detect_image($request)
        {
            global $wp, $wpdb;

            // Allowed MIME types
            $mime_types_array = $this->piuma_get_allowed_extensions();
            $mime_types = implode("|", $mime_types_array);

            // Prepare the new directory name for REGEX rule
            $new_media_dir = preg_quote(PIO_MEDIA_DIR, '/');

            // Check if requested file is an attachment
            preg_match("/{$new_media_dir}(.+)\.({$mime_types})/", $wp->request, $is_file);

            if (!empty($is_file)) {
                // Get the uploads dir used by WordPress to host the media files
                $upload_dir = wp_upload_dir();

                // Decode the URI-encoded characters
                $filename = basename(urldecode($wp->request));

                // Check if filename contains non-ASCII characters. If does, use SQL to find the file on the server
                if (preg_match('/[^\x20-\x7f]/', $filename)) {

                    // Check if the file is a thumbnail
                    preg_match("/(.*)(-[\d]+x[\d]+)([\S]{3,4})/", $filename, $is_thumb);

                    // Prepare the pattern
                    $pattern = "{$upload_dir['baseurl']}/%/{$filename}";

                    // Use the full size URL in SQL query (remove the thumb appendix)
                    $pattern = (!empty($is_thumb[2])) ? preg_replace("/(-[\d]*x[\d]*)/", "", "{$upload_dir['baseurl']}/%/{$filename}") : $pattern;

                    $file_url = $wpdb->get_var($wpdb->prepare("SELECT guid FROM $wpdb->posts WHERE guid LIKE %s", $pattern));

                    if (!empty($file_url)) {
                        // Replace the URL with DIR
                        $file_dir = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);

                        // Get the original path
                        $file_dir = (!empty($is_thumb[2])) ? str_replace($is_thumb[1], "{$is_thumb[1]}{$is_thumb[2]}", $file_dir) : $file_dir;
                    }
                } else {
                    // Prepare the pattern
                    $pattern = "{$upload_dir['basedir']}/*/{$filename}";

                    $found_files = $this->piuma_find_file($pattern);

                    // Get the original path if file is found
                    $file_dir = (!empty($found_files[0])) ? $found_files[0] : false;
                }
            }

            // Double check if the file exists
            if (!empty($file_dir) && file_exists($file_dir)) {
                $file_mime = mime_content_type($file_dir);

                // Set headers
                header('Content-type: ' . $file_mime);
                readfile($file_dir);
                die();
            }
            return $request;
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


        public function piuma_replace_lazyload($content)
        {
            $placeholder = get_template_directory_uri() . '/assets/placeholders/squares.svg';

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
            $lazy_load_images = $xpath->query("//img[ contains( concat( ' ', normalize-space( @class ), ' '), ' lazy-load ' ) ]");

            // Process image HTML
            foreach ($lazy_load_images as $node) {
                $fallback = $node->cloneNode(true);

                $oldsrc = $node->getAttribute('src');
                $node->setAttribute('data-src', $oldsrc);
                $newsrc = $placeholder;
                $node->setAttribute('src', $newsrc);

                $oldsrcset = $node->getAttribute('srcset');
                $node->setAttribute('data-srcset', $oldsrcset);
                $newsrcset = '';
                $node->setAttribute('srcset', $newsrcset);

                $noscript = $dom->createElement('noscript', '');
                $node->parentNode->insertBefore($noscript, $node);
                $noscript->appendChild($fallback);
            }

            // Save and return updated HTML.
            $new_content = $dom->saveHTML();
            return  $new_content;
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
