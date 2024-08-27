<?php
function getMastodonPosts(string $tag)
{
    $url =  "https://mastodon.social/api/v1/timelines/tag/$tag?limit-12";
    $json_data = file_get_contents($url); // retrieves the result in JSON format
    $response_data = json_decode($json_data); // converts the result from JSON to a PHP format
    return $response_data;
}

function getDnsRecordValue(array $record): int | string
{
    switch($record['type']) {
        case 'A':
            return $record['ip'];
        case 'MX':
            return "{$record['pri']} {$record['target']}";
        case 'TXT':
            return $record['txt'];
        case 'AAAA':
            return $record['ipv6'];
        case 'CNAME':
        case 'NS':
        case 'PTR':
            return $record['target'];
        default:
            return 'Unsupported record type';
    }
}
?>

<!doctype html>
<html lang="en">

<?php include 'includes/head.php' ?>

<body <?= isset($_COOKIE['darkmode']) && $_COOKIE['darkmode'] === 'true' ? 'data-bs-theme="dark"' : '' ?>>
    <?php include 'includes/menu.php' ?>

    <div class="container">
        <h1>PHP @ Mastodon</h1>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <!-- Posts go here -->
            <?php
            foreach (getMastodonPosts('php') as $post) {
            ?>
            <div class="col">
                <div class="card h-100">
                    <?php
                    if (isset($post->media_attachments[0])) {
                        echo "<img src='{$post->media_attachments[0]->preview_url}' class='card-img-top'>";
                    }
                    ?>
                    <div class="card-header">
                        <p>Data retrieved from the following <a href="<?= $post->url ?>" target="_blank">link</a></p>
                    </div>
                    <div class="card-body">
                        <p><?= $post->content ?></p>
                    </div>
                    <div class="card-footer">
                        <?= "<img src='{$post->account->avatar_static}' style='max-width: 30px; border-radius: 50%'>"; ?>
                        <?= $post->account->display_name ?>
                    </div>
                </div>
            </div>
            <?php } ?>
            <p>
                Data retrieved from <a href="https://mastodon.social" target="_blank">mastodon.social</a>
            </p>
            <table class="table">
                <thead>
                    <tr>
                        <th>Record Type</th>
                        <th>Value</th>
                        <th>TTL</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DNS Info goes here -->
                    <?php
                    $mastodonDns = dns_get_record("mastodon.social", DNS_ALL);
                    foreach ($mastodonDns as $key => &$value) {
                        echo '<tr>';
                        echo "<td>{$value['type']}</td>";
                        echo "<td>" . getDnsRecordValue($value) . "</td>";
                        echo "<td>{$value['ttl']}</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
            session_start();
            function loadValue(string $key, bool $json = false): mixed
            {
                $result = null;
                if (isset($_SESSION[$key])) {
                    $result = $_SESSION[$key];
                } elseif (isset($_COOKIE[$key])) {
                    $result = $json ? json_decode($_COOKIE[$key], true) : $_COOKIE($key);
                    $_SESSION[$key] = $result;
                }
                return $result;
            }

            function saveValue(string $key, mixed $value, bool $json = false, int $exp = 2628288): void
            {
                $_SESSION[$key] = $value;
                setcookie($key, $json ?json_encode($value) : $value, time() + $exp, '/');
            }
        ?>
        <?php include 'includes/footer.php' ?>
</body>

</html>