<?php
class WP_Donation_System_Updater {
    private $plugin_slug;
    private $version;
    private $cache_key;
    private $cache_allowed;
    private $github_repo;
    private $github_username;

    public function __construct() {
        $this->plugin_slug = dirname(plugin_basename(WP_DONATION_SYSTEM_PATH));
        $this->version = WP_DONATION_SYSTEM_VERSION;
        $this->cache_key = 'wp_donation_system_updater';
        $this->cache_allowed = true;
        $this->github_username = 'victorKariuki';
        $this->github_repo = 'wp-donation-system';

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_action('upgrader_process_complete', array($this, 'purge_cache'));
    }

    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_info = $this->get_remote_info();
        if (false === $remote_info) {
            return $transient;
        }

        if (version_compare($this->version, $remote_info->tag_name, '<')) {
            $transient->response[$this->plugin_slug] = (object) array(
                'slug' => $this->plugin_slug,
                'new_version' => $remote_info->tag_name,
                'package' => $remote_info->zipball_url,
                'tested' => '6.4', // Latest WordPress version tested
                'requires' => '5.0',
                'compatibility' => new stdClass(),
            );
        }

        return $transient;
    }

    public function plugin_info($result, $action, $args) {
        if ('plugin_information' !== $action) {
            return $result;
        }

        if ($this->plugin_slug !== $args->slug) {
            return $result;
        }

        $remote_info = $this->get_remote_info();

        if (!$remote_info) {
            return $result;
        }

        $result = (object) array(
            'name' => 'WP Donation System',
            'slug' => $this->plugin_slug,
            'version' => $remote_info->tag_name,
            'author' => '<a href="https://github.com/' . $this->github_username . '">' . $this->github_username . '</a>',
            'requires' => '5.0',
            'tested' => '6.4',
            'last_updated' => date('Y-m-d', strtotime($remote_info->published_at)),
            'homepage' => 'https://github.com/' . $this->github_username . '/' . $this->github_repo,
            'download_link' => $remote_info->zipball_url,
            'sections' => array(
                'description' => $this->get_github_readme(),
                'changelog' => $this->get_github_changelog(),
            )
        );

        return $result;
    }

    private function get_remote_info() {
        if ($this->cache_allowed) {
            $cached = get_transient($this->cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        $response = wp_remote_get(sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            $this->github_username,
            $this->github_repo
        ), array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json'
            ),
            'timeout' => 10
        ));

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return false;
        }

        $info = json_decode(wp_remote_retrieve_body($response));

        if ($this->cache_allowed) {
            set_transient($this->cache_key, $info, 12 * HOUR_IN_SECONDS);
        }

        return $info;
    }

    private function get_github_readme() {
        $response = wp_remote_get(sprintf(
            'https://api.github.com/repos/%s/%s/readme',
            $this->github_username,
            $this->github_repo
        ), array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3.html'
            )
        ));

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return '';
        }

        return wp_remote_retrieve_body($response);
    }

    private function get_github_changelog() {
        $response = wp_remote_get(sprintf(
            'https://api.github.com/repos/%s/%s/contents/CHANGELOG.md',
            $this->github_username,
            $this->github_repo
        ), array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3.html'
            )
        ));

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return '';
        }

        return wp_remote_retrieve_body($response);
    }

    public function purge_cache() {
        delete_transient($this->cache_key);
    }
} 