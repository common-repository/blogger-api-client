<?php
require_once(realpath(dirname(__FILE__)) . '/' . '../../../wp-includes/class-IXR.php');

class bloggerclient {

    var $bServer;
    var $bPath;
    var $apiName = "blogger";
    var $blogClient;
    var $XMLappID;
    var $XMLusername;
    var $XMLpassword;

    function bloggerclient($server, $path, $appId, $username, $password)
    {
	$this->bServer = $server;
	$this->bPath = $path;

        // Connect to blogger server
	if (!$this->connectToBlogger()) {
	    return false;
	}

    	// Create variables to send in the message
    	$this->XMLappID	   = $appId;
    	$this->XMLusername = $username;
    	$this->XMLpassword = $password;
    	return $this;
    }

    function getUsersBlogs()
    {
    	// Construct query for the server
        $r = $this->blogClient->query($this->apiName . ".getUsersBlogs", $this->XMLappID, $this->XMLusername, $this->XMLpassword);
    	return $this->blogClient->getResponse();
    }

    function getUserInfo()
    {
        $r = $this->blogClient->query($this->apiName . ".getUserInfo", $this->XMLappID, $this->XMLusername, $this->XMLpassword);
        return $this->blogClient->getResponse();
    }
        
    function getRecentPosts($blogID, $numPosts)
    {
        $XMLblogid = $blogID;
        $XMLnumPosts = $numPosts;
	
        $r = $this->blogClient->query($this->apiName . ".getRecentPosts", $this->XMLappID, $XMLblogid, $this->XMLusername, $this->XMLpassword, $XMLnumPosts);

        return $this->blogClient->getResponse();
    }
        
    function getPost($postID)
    {
        $XMLpostid = $postID;
        $r = $this->blogClient->query($this->apiName . ".getPost", $this->XMLappID, $XMLpostid, $this->XMLusername, $this->XMLpassword);
        return $this->blogClient->getResponse();
    }

    function newPost($blogID, $textPost, $publish=false)
    {
        $XMLblogid = $blogID;
        $XMLcontent = $textPost;
        $XMLpublish = $publish;
        $r = $this->blogClient->query($this->apiName . ".newPost", $this->XMLappID, $XMLblogid, $this->XMLusername, $this->XMLpassword, $XMLcontent, $XMLpublish);
        return $this->blogClient->getResponse();
    }
        
    function editPost($blogID, $textPost, $publish=false)
    {
        $XMLblogid = $blogID;
        $XMLcontent = $textPost;
        $XMLpublish = $publish;
        $r = $this->blogClient->query($this->apiName . ".editPost", $this->XMLappID, $XMLblogid, $this->XMLusername, $this->XMLpassword, $XMLcontent, $XMLpublish);
        return $this->blogClient->getResponse();
    }
        
    function deletePost($postID, $publish=false)
    {
        $XMLpostid = $postID;
        $XMLpublish = $publish;
        $r = $this->blogClient->query($this->apiName . ".deletePost", $this->XMLappID, $XMLpostid, $this->XMLusername, $this->XMLpassword, $XMLpublish);
        return $this->blogClient->getResponse();
    }
        
    function getTemplate($blogID, $template="main")
    {
        $XMLblogid = $blogID;
        $XMLtemplate = $template;
        $r = $this->blogClient->query($this->apiName . ".getTemplate", $this->XMLappID, $XMLblogid, $this->XMLusername, $this->XMLpassword, $XMLtemplate);
        return $this->blogClient->getResponse();
    }
        
    function setTemplate($blogID, $template="archiveIndex")
    {
        $XMLblogid = $blogID;
        $XMLtemplate = $template;
        $r = $this->blogClient->query($this->apiName . ".setTemplate", $this->XMLappID, $XMLblogid, $this->XMLusername, $this->XMLpassword, $XMLtemplate);
        return $this->blogClient->getResponse();
    }

    // class helper functions
    // Returns a connection object to the blogger server
    function connectToBlogger() {
    	if($this->blogClient = new IXR_Client($this->bServer, $this->bPath)) {
    		return true;
    	} else {
    		return false;
    	}
    }
}
?>
