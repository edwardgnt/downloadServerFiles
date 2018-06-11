<?php

/**
 * Date: 1/23/17
 * Time: 2:22 PM
 *
 * This class downloads the reply and error files from the vendor's(remote) server to our designated local directory
 * nlist, get, and delete functions are called from the SFTP class in SFTP.php
 * getErrors function are called from the SSH2 class in SSH2.php
 */
class VendorServer
{
    private $connection;
    private $localDirectory;
    private $remoteDirectory;
    private $fileDirRegex;
    private $errorFileRegex;
    private $successFileRegex;
    private $errors = [];

    /**
     * VendorServer constructor.
     */
    public function __construct()
    {
        // SFTP connection
        $this->connection = new \Sftp\RemoteServer();

        // Local directory where files will download
        $this->localDirectory = "/assets/reply_files/status/";
        $this->fileDirRegex = "/(0301|0601)([0-9A-Z]{4})/";

        // Regex for error files
        $this->errorFileRegex = "/^[0-9]{8}[0-9]{6}-errors.txt$/";

        // Regex for success or reply files
        $this->successFileRegex = "/[0-9]{6}-reply.txt$/";
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Gets a list of files and dowwnloads files from vendor's server via sftp
     * @return bool|int
     * @throws \Exception
     */
    public function downloadFiles()
    {
        $isIssue = false;
        $downloaded = 0;
        $dirs = $this->connection->nlist();

        foreach ($dirs as $dir) {
            if (preg_match($this->fileDirRegex, $dir)) {

                // This the location in the server where we are getting the files from
                $this->remoteDirectory = "/$dir/error/";

                // Gets a list of files so that we can do a regex match
                $files = $this->connection->nlist($this->remoteDirectory);

                if (!is_array($files)) {
                    continue;
                }

                foreach ($files as $file) {

                    // Does the regex matching to find the error files
                    if (preg_match($this->errorFileRegex, $file)) {
                        if (!is_dir($this->localDirectory)) {
                            if (!mkdir($this->localDirectory, 0755, true)) {
                                throw new \Exception("Unable to create " . $this->localDirectory);
                            }
                        }

                        sleep(1);

                        // Calls get function to download the file from the vendor's server
                        $ok = $this->connection->get($this->remoteDirectory . $file, $this->localDirectory . $file);

                        if (!$ok) {
                            $this->errors[] = "Not Successful";
                            $this->errors[] = "File: " . $file;
                            $this->errors[] = "Remote path: " . $this->remoteDirectory;
                            $this->errors[] = "Local Path: " . $this->remoteDirectory;

                            $this->errors = array_merge($this->errors, $this->connection->getErrors());
                        } else {

                            // Downloaded file count
                            $downloaded++;

                            // Deletes the files off the remote server
                            $this->connection->delete($this->remoteDirectory . $file);
                        }
                    }
                }
            }
        }

        foreach ($dirs as $dir) {

            if (preg_match($this->fileDirRegex, $dir)) {

                $this->remoteDirectory = "/$dir/reply/";

                // Gets a list of files so that we can do a regex match
                $files = $this->connection->nlist($this->remoteDirectory);

                if (!is_array($files)) {
                    continue;
                }

                foreach ($files as $file) {

                    // Does the regex matching to find the success or reply files
                    if (preg_match($this->successFileRegex, $file)) {

                        // Goes into the get function to download the file from their server to our location
                        $ok = $this->connection->get($this->remoteDirectory . $file, $this->localDirectory . $file);

                        if (!$ok) {
                            $this->errors[] = "Not Successful";
                            $this->errors[] = "File: " . $file;
                            $this->errors[] = "Remote path: " . $this->remoteDirectory;
                            $this->errors[] = "Local Path: " . $this->remoteDirectory;

                            $this->errors = array_merge($this->errors, $this->connection->getErrors());
                        } else {

                            // Downloaded file count
                            $downloaded++;

                            // Deletes the files from the vendor's server
                            $this->connection->delete($this->remoteDir . $file);
                        }
                    }
                }
            }
        }

        // Returns downloaded if there is no issue, but returns false if there is a problem
        return $isIssue ? false : $downloaded;
    }
}
