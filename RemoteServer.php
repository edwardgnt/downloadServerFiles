<?php
/**
 * Date: 2/10/17
 * Time: 12:11 PM
 */

namespace Sftp;

use phpseclib\Net\SFTP;

class RemoteServer extends SFTP
{
    private $devHost = "";
    private $devPassword = "";
    private $devUser = "user";
    private $productionHost = '';
    private $productionPassword = '';
    private $productionUser = '';
    private $sftpHost;

    /**
     * RemoteServer constructor.
     */
    public function __construct()
    {
        // CodeIgniter's environment variable
        if (defined('ENVIRONMENT')) {
            switch (ENVIRONMENT) {
                case 'production':
                    $this->productionConnection();
                    break;
                case 'development':
                    $this->devConnection();
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown ENVIRONMENT set:' . ENVIRONMENT);
                    break;
            }
        }
    }

    /**
     * @param string $knownHostsFile
     * @return bool
     */
    public function checkHostKey($knownHostsFile = '')
    {
        if ($knownHostsFile == '') {
            $knownHostsFile = __DIR__ . '/known_hosts';
        }

        $hostKey = $this->getServerPublicHostKey();

        if (!file_exists($knownHostsFile) || !is_readable($knownHostsFile)) {
            return false;
        }

        $pattern = '/^([^ ]*,)*' . $this->sftpHost . '(,[^ ]*)* /';
        foreach (preg_grep($pattern, file($knownHostsFile)) as $line) {
            $spacePos = strpos($line, ' ');
            if ($spacePos === false || strlen(trim($line)) < $spacePos + 2) {
                return false;
            }
            if (substr_compare(trim($line), $hostKey, $spacePos + 1) == 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    private function devConnection()
    {
        $this->sftpHost = $this->devHost;

        parent::__construct($this->sftpHost);

        if (!$this->login($this->devUser, $this->devPassword)) {
            throw new \Exception('SFTP login failed!');
        }
    }

    /**
     * @throws \Exception
     */
    private function productionConnection()
    {
        $this->sftpHost = $this->productionHost;
        parent::__construct($this->sftpHost);

        if ($this->checkHostKey()) {
            if (!$this->login($this->productionUser, $this->productionPassword)) {
                throw new \Exception('SFTP login failed!');
            }
        } else {
            $this->disconnect();
            throw new \Exception('Host key verification failed!');
        }
    }
}
