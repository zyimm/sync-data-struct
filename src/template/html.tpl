<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>code</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="https://cdn.staticfile.org/prism/9000.0.1/themes/prism.min.css">
</head>
<body>
    <?php foreach ($diff as $type => $sql_list): ?>
        <h2><?php echo $type; ?></h2>
        <?php foreach ($sql_list as $sql): ?>
        <?php echo "<pre><code class='language-sql'>{$sql}</code></pre>"; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
<script src="https://cdn.staticfile.org/prism/9000.0.1/prism.min.js"></script>
<script src="https://cdn.staticfile.org/prism/9000.0.1/components/prism-sql.min.js"></script>
</body>
</html>