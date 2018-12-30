# beevrr-cron
Routine functions for [beevrr](https://www.github.com/01mu/beevrr). Sets discussions at phase `pa_phase` to `argument`, discussions at phase `argument` to `post-argument`, and discussions at `post-argument` to `finished`. Discussions at phase `finished` have their winners determined. Updates are logged in `update_logs`.
## Usage
Run `driver.php` at an interval less than the shortest phase length.
