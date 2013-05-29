<?php

ignore_user_abort(true);

include 'config.php';

function get_feature_from_branch_name($branch)
{
	return preg_replace('/^.*feature\//', '',$branch);
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
	
	if($numArgs > 0) {
		$arg_list = array_slice(func_get_args(), 1);
		
		// start after $str
		for($i=0; $i < $numArgs; $i++) {
			$str = str_replace("{" . $i . "}", $arg_list[$i], $str);
		}
	}

	return $str;
}

class git
{

	public $git_path = 'git';

    protected $repo_path = null;


     /**
	 * Constructor
	 *
	 * Accepts a repository path
	 *
	 * @access  public
	 * @param   string  repository path
	 * @param   bool    create if not exists?
	 * @return  void
	 */
	public function __construct($repo_path = null) {
		if (is_string($repo_path))
			$this->set_repo_path($repo_path);
	}


/**
	 * Set the repository's path
	 *
	 * Accepts the repository path
	 *
	 * @access  public
	 * @param   string  repository path
	 * @param   bool    create if not exists?
	 * @return  void
	 */
	public function set_repo_path($repo_path) {
		$this->repo_path = $repo_path;
	}


	/**
	 * Run a command in the git repository
	 *
	 * Accepts a shell command to run
	 *
	 * @access  protected
	 * @param   string  command to run
	 * @return  string
	 */
	protected function run_command($command) {
		$descriptorspec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
		$pipes = array();

        $resource = proc_open($command, $descriptorspec, $pipes, $this->repo_path);

		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		foreach ($pipes as $pipe) {
			fclose($pipe);
		}

		$status = trim(proc_close($resource));
		if ($status) throw new Exception($stderr);
        return $stdout;
	}

    /**
	 * Run a git command in the git repository
	 *
	 * Accepts a git command to run
	 *
	 * @access  public
	 * @param   string  command to run
	 * @return  string
	 */
	public function run($command) {
		$result = $this->run_command($this->git_path." ".$command);
        return $result;
	}

    public function pull_all()
    {

      $this->run("checkout master");
      $result = $this->run("pull origin master");
      return $result;
    }


    public function current_branch()
    {
    	$result = $this->run("rev-parse --abbrev-ref HEAD");
        return $result;
    }

    public function show_config()
    {
    	$result = $this->run("config -l");
    	return $result;
    }

    public function repository_clone($repository)
    {
    	$result = $this->run("clone --recursive ".$repository);
    	return $result;
    }

    public function checkout_remote()
    {
    	$branches = $this->run("branch -r");
    	$branchArray = explode("\n",$branches);
    	foreach($branchArray as $i => &$branch) {
			$branch = trim($branch);
    		$branch = str_replace("* ", "", $branch);
    		$branch = preg_replace("/ .*$/", '', $branch);
    		if ($branch == "" || preg_match("/master/", $branch))
				unset($branchArray[$i]);
		}
		//print_r($branchArray);
		foreach ($branchArray as $branch) {
			try {
			  		$this->run("checkout --track " . $branch . " --force");
		       }
		     catch (Exception $e) {
                	//var_dump($e->getMessage());
               }
		}
    }

    public function feature_checkout($feature)
    {
        if ($feature != 'develop')
        {
          $result = $this->run("flow feature checkout " . $feature);	
        }
        else
        {
          $result = $this->run("checkout develop");		
        }
        
        return $result;
    }

    public function list_features($keep_asterisk = false)
    {
    	$branchArray = explode("\n", $this->run("flow feature list"));
    	foreach($branchArray as $i => &$branch) {
			$branch = trim($branch);
			if (! $keep_asterisk)
				$branch = str_replace("* ", "", $branch);
			if ($branch == "")
				unset($branchArray[$i]);
		}
		return $branchArray;
    }

    public function flow_init()
    {
    	$this -> run("git flow init -d");
    }

	/**
	 * Runs a `git branch` call
	 *
	 * @access  public
	 * @param   bool    keep asterisk mark on active branch
	 * @return  array
	 */
	public function list_branches($keep_asterisk = false) {
		$branchArray = explode("\n", $this->run("branch"));
		foreach($branchArray as $i => &$branch) {
			$branch = trim($branch);
			if (! $keep_asterisk)
				$branch = str_replace("* ", "", $branch);
			if ($branch == "")
				unset($branchArray[$i]);
		}
		return $branchArray;
	}

		public function list_remote_branches($keep_asterisk = false) {
		$branchArray = explode("\n", $this->run("branch -r"));
		foreach($branchArray as $i => &$branch) {
			$branch = trim($branch);
			if (! $keep_asterisk)
				$branch = str_replace("* ", "", $branch);
			if ($branch == "")
				unset($branchArray[$i]);
		}
		return $branchArray;
	}
}

$git_path = 'git';

$repositories_config = $configuration["repositories"];

reset($repositories_config);
$current_repo = key($repositories_config);

if (isset($_REQUEST["repo"]) && array_key_exists("repo", $_REQUEST))
{
   $current_repo = $_REQUEST["repo"];
}

$current_config = $repositories_config[$current_repo];



$dir = $configuration["repository_root"] ."/".$current_config["repo_path"];

if (! is_dir($dir."/.git"))
{
   $dir = $configuration["repository_root"];
}


chdir($dir);

$git_client = new git($dir);

$command = '';

if (isset($_POST)) {
	if (array_key_exists('change_branch',$_POST))
	{
	  $command = 'change_branch';	
	}
	if (array_key_exists('init_repository',$_POST))
	{
	   $command = 'init_repository';
	}
} else if (array_key_exists('command',$_REQUEST))
{
    $command = $_REQUEST['command'];
}

switch ($command) {
	case 'change_branch':
	   $feature = $_REQUEST['selected_branch'];
       $git_client -> feature_checkout($feature);
	break;
	case 'init_repository':
	   $git_client -> repository_clone($current_config['git']);
	   $git_client -> checkout_remote();
       $git_client -> flow_init();
	break;
	default:
		# code...
		break;
}

if (is_dir($dir."/.git"))
{
$current_branch = get_feature_from_branch_name($git_client->current_branch());
$list_remote_branches = $git_client->list_remote_branches();

$result = array();
foreach ($list_remote_branches as $branch) {
	 if (preg_match('/^.*\/feature/', $branch))
	 {
       $result[] = get_feature_from_branch_name($branch);
	 }
}
$result[] = 'develop';
}
//var_dump($configuration);

?>


<html>

<body>

<h1>Select repository</h1>
<ul>
<?php 
foreach ($configuration["repositories"] as $key => $value) {
	echo string_format('<li><a href="index.php?repo={0}">{0}</a></li>',$key);
}
?>	
</ul>

<h2>Current repository: <?php echo $current_repo;?></h2>

<form method="post" action="index.php">	

<?php if (is_dir($dir."/.git")) { ?>

<h3>On branch:&nbsp;<?php echo $current_branch;?></h3> 

<input name="repo" type="hidden" value = "<?php echo $current_repo;?>"/>	
<select id="selected_branch" name = "selected_branch">
	<option value="" disabled="disabled" selected="selected">Please select a feature</option>
<?php
foreach ($result as $feature)
{
	echo string_format('<option value="{0}">{0}</option>',$feature);
}
?>
</select>
 <p><input type="submit" name="change_branch" value="Checkout"/></p>

 <?php }  else { ?>
  <!-- 
 <p><input type="submit" name="init_repository" value="Init Repository"/></p>
  -->
<?php } ?>
</form>
</body>
</html>