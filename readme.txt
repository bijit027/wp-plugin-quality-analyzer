=== WP Plugin Quality Analyzer ===
Contributors: bijit027
Tags: research, plugin quality, metrics, thesis
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Empirical research tool for analyzing software quality metrics across WordPress plugins. Built for MS CS thesis research.

== Description ==

WP Plugin Quality Analyzer is a custom-built research instrument designed programmatically to collect, analyze, and visualize software quality metrics from the WordPress.org Plugin Directory. 

This tool implements the proprietary Plugin Health Score (PHS) framework and generates the dataset used in the author's MS CS thesis at Florida Polytechnic University.

Features include:
* Automated data fetching from the WordPress.org Plugin API
* Calculation of the Plugin Health Score (PHS) based on update frequency, user ratings, and support resolution rates
* Dashboard visualizations of metric distributions
* CSV and JSON dataset export for external analysis

== Installation ==

1. Upload the `wp-plugin-quality-analyzer` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the 'Plugin Analyzer' dashboard from the WordPress admin menu.

== Frequently Asked Questions ==

= Is this suitable for production sites? =
No. This is a research tool designed specifically for academic data collection. It is not intended to be run on live production websites, as the data collection process can be resource-intensive.

== Changelog ==

= 1.0.0 =
* Initial release for data collection phase.
* Implemented Plugin Health Score calculator.
* Added data visualizations and export capabilities.
