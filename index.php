<?php
ignore_user_abort(true);

// config
// use: config_sample.php
include 'config.php';

// git client class
include 'git_client.php';

/*
 * TODO: fix git fetch access issue
 */

function get_feature_from_branch_name($branch) {
    return preg_replace('/^.*feature\//', '', $branch);
}

/**
 * Formats a string with zero-based placeholders
 * {0}, {1} etc corresponding to an array of arguments
 * Must pass in a string and 1 or more arguments
 */
function string_format($str) {
    // replaces str "Hello {0}, {1}, {0}" with strings, based on
    // index in array
    $numArgs = func_num_args() - 1;

    if ($numArgs > 0) {
        $arg_list = array_slice(func_get_args(), 1);

        // start after $str
        for ($i = 0; $i < $numArgs; $i++) {
            $str = str_replace("{" . $i . "}", $arg_list[$i], $str);
        }
    }

    return $str;
}

$repositories_config = $configuration["repositories"];
reset($repositories_config);
$current_repo = key($repositories_config);

if (isset($_REQUEST["repo"]) && array_key_exists("repo", $_REQUEST)) {
    $current_repo = $_REQUEST["repo"];
}

$current_config = $repositories_config[$current_repo];

$dir = $configuration["repository_root"] . "/" . $current_config["repo_path"];

if (!is_dir($dir . "/.git")) {
    $dir = $configuration["repository_root"];
}

chdir($dir);

$git_client = new git($dir);

$command = '';

if (isset($_POST)) {
    if (array_key_exists('change_branch', $_POST)) {
        $command = 'change_branch';
    }
    if (array_key_exists('init_repository', $_POST)) {
        $command = 'init_repository';
    }
} else if (array_key_exists('command', $_REQUEST)) {
    $command = $_REQUEST['command'];
}

switch ($command) {
    case 'change_branch':
        $feature = $_REQUEST['selected_branch'];
        $git_client->feature_checkout($feature);
        break;
    case 'init_repository':
        $git_client->repository_clone($current_config['git']);
        $git_client->checkout_remote();
        $git_client->flow_init();
        break;
    default:
        # code...
        break;
}

if (is_dir($dir . "/.git")) {
    $current_branch = get_feature_from_branch_name($git_client->current_branch());
    //$git_client->fetch_all();
    $list_remote_branches = $git_client->list_remote_branches();

    $result = array();
    foreach ($list_remote_branches as $branch) {
        if (preg_match('/^.*\/feature/', $branch)) {
            $result[] = get_feature_from_branch_name($branch);
        }
    }
    $result[] = 'develop';
}
?>


<html>

    <body>
        <h1>Select repository</h1>
        <ul>
<?php
foreach ($configuration["repositories"] as $key => $value) {
    echo string_format('<li><a href="index.php?repo={0}">{0}</a></li>', $key);
}
?>	
        </ul>

        <h2>Current repository: <?php echo $current_repo; ?></h2>

        <form method="post" action="index.php">	

<?php if (is_dir($dir . "/.git")) { ?>

                <h3>On branch:&nbsp;<?php echo $current_branch; ?></h3> 

                <input name="repo" type="hidden" value = "<?php echo $current_repo; ?>"/>	
                <select id="selected_branch" name = "selected_branch">
                    <option value="" disabled="disabled" selected="selected">Please select a feature</option>
                <?php
                foreach ($result as $feature) {
                    echo string_format('<option value="{0}">{0}</option>', $feature);
                }
                ?>
                </select>
                <p><input type="submit" name="change_branch" value="Checkout"/></p>

<?php } else { ?>
                <!-- 
               <p><input type="submit" name="init_repository" value="Init Repository"/></p>
                -->
<?php } ?>
        </form>
    </body>
</html>