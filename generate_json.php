<?php
require_once __DIR__ . "/config.inc.php";
require_once __DIR__ . "/vendor/autoload.php";

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitRepository;
use CzProject\GitPhp\GitException;

function generateOutputFiles(array $result): void {
    @mkdir("output");
    writeResult($result);
    copy("template/index.html", "output/index.html");
}

function writeResult(array $result): void {
    $file = fopen("output/data.json", "w");
    fwrite($file, json_encode($result));
    fclose($file);
}

function getPhpRepo(): GitRepository {
    return (new Git)->open(PHP_SRC_PATH);
}

function main(): void {
    $php_src_repo = getPhpRepo();
    $result = [];
    $benchmarks = [];

    foreach (glob(BENCHMARK_DATA_PATH . "/*/*/summary.json") as $dir) {
        preg_match('|/[a-z0-9]+/([a-z0-9]+)/summary.json$|', $dir, $matches);
        list($file, $commit_id) = $matches;

        try {
            $commit_info = $php_src_repo->getCommit($commit_id);
            echo "Retrieved info about commit $commit_id\n";
        } catch (GitException $e) {
            echo "Could not retrieve info about commit $commit_id: ", $e->getMessage(), "\n";
            continue;
        }

        $summary_data = json_decode(file_get_contents(BENCHMARK_DATA_PATH . $file));
        foreach (array_keys((array) $summary_data) as $benchmark_name) {
            $benchmarks[] = $benchmark_name;
        }

        $data = [
            "commit_info" => [
                "subject" => $commit_info->getSubject(),
                "date" => $commit_info->getCommitterDate(),
                "id" => $commit_id,
            ],
            "summary" => $summary_data,
        ];
        $result[] = $data;
    }

    $benchmarks = array_unique($benchmarks);
    generateOutputFiles(['result' => $result, 'benchmarks' => array_values($benchmarks)]);
}

main();
