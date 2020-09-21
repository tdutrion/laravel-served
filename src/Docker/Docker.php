<?php

namespace Sinnbeck\LaravelServed\Docker;

use Sinnbeck\LaravelServed\Shell\Shell;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Sinnbeck\LaravelServed\Exceptions\DockerNotRunningException;
use Sinnbeck\LaravelServed\Exceptions\DockerNotInstalledException;

class Docker
{
    /**
     * @var \Sinnbeck\LaravelServed\Shell\Shell
     */
    protected $shell;

    public function __construct(Shell $shell)
    {
        $this->shell = $shell;
    }

    public function verifyDockerIsInstalled(): void
    {
        try {
            $this->version();

        } catch (ProcessFailedException $e) {
            throw new DockerNotInstalledException('Docker is missing!');
        }
    }

    public function verifyDockerDemonIsRunning(): void
    {
        try {
            $this->shell->exec('docker info');

        } catch(ProcessFailedException $e) {
            throw new DockerNotRunningException('Docker isn\'t running');
        }
    }

    public function version()
    {
        return $this->shell->exec('docker version --format="{{json .Client.Version}}"');
    }

    public function ensureNetworkExists($name)
    {
        try {
           $this->shell->exec('docker network inspect "${:name}"', ['name' => $name]);

        }
        catch (ProcessFailedException $e) {
            //Make network

            $this->shell->exec('docker network create "${:name}"', ['name' => $name]);
        }
    }

    public function removeNetwork(string $name): void
    {
        $this->shell->exec('docker network rm ' . $name);
    }

    public function listContainers()
    {
        $name = 'served_' . config('served.name') . '_';
        $containers = $this->shell->exec('docker ps --all --filter "name=' . $name . '" --format "{{.ID}}|{{.Names}}|{{.Image}}|{{.Status}}|{{.Ports}}"');
        $formatted = collect(explode("\n", $containers))->filter()->map(function($row) {
            return  explode('|', $row);
        })->reverse();

        return $formatted->prepend([
            'ID', 'Name', 'Image', 'Status', 'Used ports'
        ]);

    }

}
