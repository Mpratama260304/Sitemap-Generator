<?php

/**
 * Script untuk generate sample CSV dengan banyak URL untuk testing
 * 
 * Cara penggunaan:
 * php scripts/generate-sample-csv.php 100000 > storage/app/large-sample.csv
 * 
 * Argument: jumlah URL yang akan di-generate
 */

$count = isset($argv[1]) ? (int)$argv[1] : 1000;

// Header
echo "url,lastmod,changefreq,priority\n";

$categories = ['artikel', 'berita', 'tutorial', 'tips', 'review', 'panduan'];
$changefreqs = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly'];

for ($i = 1; $i <= $count; $i++) {
    $category = $categories[array_rand($categories)];
    $slug = "post-" . $i . "-" . bin2hex(random_bytes(4));
    $date = date('Y-m-d', strtotime("-" . rand(0, 365) . " days"));
    $changefreq = $changefreqs[array_rand($changefreqs)];
    $priority = number_format(rand(1, 10) / 10, 1);
    
    echo "/{$category}/{$slug},{$date},{$changefreq},{$priority}\n";
    
    // Flush output untuk memory efficiency
    if ($i % 10000 === 0) {
        fflush(STDOUT);
    }
}
