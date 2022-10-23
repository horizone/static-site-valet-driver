<?php

class StaticSiteValetDriver extends ValetDriver
{
    public function serves($sitePath, $siteName, $uri)
    {
        // If this is a Laravel installation then we will not serve it with this driver
        if (file_exists($sitePath.'/artisan')) return false;
        // ... might need to add more exceptions for different frameworks here

        // Use public folder as site root if one is present
        if (file_exists($sitePath.'/public')) {
            $sitePath = $sitePath.'/public';
        }

        // Is this a directory
        if (is_dir($sitePath . $uri)) {

            // Real folder names in urls need to end with a slash for browser to know
            // how to use links that is local to the folder. If there is no slash
            // at the end, then redirect with a slash and keep params.
            if (substr($sitePath . $uri,-1) != "/") {
                $params = "";
                if (strpos($_SERVER['REQUEST_URI'], "?") !== false) {
                    $params = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], "?"));
                }
                header('location: http://'.$_SERVER['HTTP_HOST'].$uri."/".$params); exit;
            }

            // Check for a index file in this directory
            if (file_exists($sitePath . $uri . "index.php")) {
                $fileName = "index.php";
            } else if (file_exists($sitePath . $uri . "index.html")) {
                $fileName = "index.html";
            } else if (file_exists($sitePath . $uri . "index.htm")) {
                $fileName = "index.htm";
            } else {
                http_response_code(403);
                echo "<h1>Forbidden</h1><p>No index file present.</p>";
                die();
            }
        } else {
            // A filename is in the url, check that it exist

            // Divide subfolders into array
            $parts = [];
            if (mb_strlen($uri) > 1 && strpos($uri,"/") !== false) {
                $parts = explode('/',$uri);
            } else {
                $parts[] = $uri;
            }

            // Extract the filename
            if (count($parts) > 0) {
                $fileName = end($parts);
                array_pop($parts);
            }

            // Rebuild without Filename
            $uri = implode("/", $parts);

            // Add a slash if needed
            $uri = strlen($uri) > 0 ? $uri . "/" : $uri;

            // Finally check if file exist
            if ( ! file_exists($sitePath . $uri . $fileName)) {
                // Non existing file
                http_response_code(404);
                echo "<h1>Not Found</h1><p>There is no ".$fileName." in ".$sitePath . $uri."</p>";
                die();
            }
        }

        // Store these temporarily
        $this->betterSitePath = $sitePath;
        $this->betterUri = $uri;
        $this->betterFileName = $fileName;
        return true;
    }

    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if (substr($this->betterFileName,-4) == ".php") return false;
        if (isset($this->betterSitePath) && isset($this->betterUri) && isset($this->betterFileName)) {
            return $this->betterSitePath . $this->betterUri . $this->betterFileName;
        }
        return false;
    }

    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        // Correct globals to be more like a static site usually
        // creates them since Valet otherwise changes them

        $_SERVER["DOCUMENT_ROOT"] = $this->betterSitePath;
        $_SERVER["SCRIPT_NAME"] = $this->betterUri . $this->betterFileName;
        $_SERVER["DOCUMENT_URI"] = $this->betterSitePath . $this->betterUri . $this->betterFileName;
        $_SERVER["PHP_SELF"] = $this->betterUri . $this->betterFileName;
        $_SERVER["SCRIPT_FILENAME"] = $this->betterSitePath . $this->betterUri . $this->betterFileName;
        set_include_path(get_include_path() . PATH_SEPARATOR . dirname($this->betterSitePath . $this->betterUri . $this->betterFileName));
        return $this->betterSitePath . $this->betterUri . $this->betterFileName;
    }
}
