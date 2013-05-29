<?php

/**
 * PHP Git Client
 */
class git {

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
        if ($status)
            throw new Exception($stderr);
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
        $result = $this->run_command($this->git_path . " " . $command);
        return $result;
    }
    
    /**
     * Pull from origin/master
     * 
     * @return string
     */
    public function pull_all() {
        $this->run("checkout master");
        $result = $this->run("pull origin master");
        return $result;
    }

    /**
     * Get current branch name
     * 
     * @return string
     */
    public function current_branch() {
        $result = $this->run("rev-parse --abbrev-ref HEAD");
        return $result;
    }

    /**
     * Get current git config
     * 
     * @return string
     */
    public function show_config() {
        $result = $this->run("config -l");
        return $result;
    }

    /**
     * Fetch all remotes
     * 
     * @return string
     */
    public function fetch_all() {
        $result = $this->run("fetch --all --prune");
        return $result;
    }

    /**
     * Clones a repository into a newly created directory
     * 
     * @param string $repository
     * @return string
     */
    public function repository_clone($repository) {
        $result = $this->run("clone --recursive " . $repository);
        return $result;
    }

    /**
     * 
     */
    public function checkout_remote() {
        $branches = $this->run("branch -r");
        $branchArray = explode("\n", $branches);
        foreach ($branchArray as $i => &$branch) {
            $branch = trim($branch);
            $branch = str_replace("* ", "", $branch);
            $branch = preg_replace("/ .*$/", '', $branch);
            if ($branch == "" || preg_match("/master/", $branch)) {
                unset($branchArray[$i]);
            }
        }
        //print_r($branchArray);
        foreach ($branchArray as $branch) {
            try {
                $this->run("checkout --track " . $branch . " --force");
            } catch (Exception $e) {
                //var_dump($e->getMessage());
            }
        }
    }

    /**
     * Change branch
     * 
     * @param string $feature Feature name (e.c. test)
     * @return string
     */
    public function feature_checkout($feature) {
        if ($feature != 'develop') {
            //$result = $this->run("flow feature checkout " . $feature);	
            $result = $this->run("checkout feature/" . $feature);
        } else {
            $result = $this->run("checkout develop");
        }

        return $result;
    }

    /**
     * Get local features list
     * 
     * @param bool $keep_asterisk
     * @return array
     */
    public function list_features($keep_asterisk = false) {
        $branchArray = explode("\n", $this->run("flow feature list"));
        foreach ($branchArray as $i => &$branch) {
            $branch = trim($branch);
            if (!$keep_asterisk)
                $branch = str_replace("* ", "", $branch);
            if ($branch == "")
                unset($branchArray[$i]);
        }
        return $branchArray;
    }

    /**
     * Git flow init
     */
    public function flow_init() {
        $this->run("git flow init -d");
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
        foreach ($branchArray as $i => &$branch) {
            $branch = trim($branch);
            if (!$keep_asterisk)
                $branch = str_replace("* ", "", $branch);
            if ($branch == "")
                unset($branchArray[$i]);
        }
        return $branchArray;
    }

    /**
     * Get list the remote-tracking branches
     * 
     * @param bool $keep_asterisk
     * @return array
     */
    public function list_remote_branches($keep_asterisk = false) {
        $branchArray = explode("\n", $this->run("branch -r"));
        foreach ($branchArray as $i => &$branch) {
            $branch = trim($branch);
            if (!$keep_asterisk)
                $branch = str_replace("* ", "", $branch);
            if ($branch == "")
                unset($branchArray[$i]);
        }
        return $branchArray;
    }

}
