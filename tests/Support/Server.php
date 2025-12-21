<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Support;

class Server{

	/** @var ?resource */
	private $process = null;

	private readonly string $host;

	private readonly int $port;

	public function __construct(string $host = '127.0.0.1'){
		$this->host = $host;
		$this->port = rand(8000, 9000);
	}

	public function getUrl(): string{
		return "http://$this->host:$this->port";
	}

	public function start(string $documentRoot): void{
		$command = sprintf(
			'php -S %s:%d -t %s',
			$this->host,
			$this->port,
			escapeshellarg($documentRoot)
		);

		$descriptors = [
			0 => ['pipe', 'r'], // stdin
			1 => ['file', (defined('PHP_WINDOWS_VERSION_BUILD') ? 'NUL' : '/dev/null'), 'w'], // stdout
			2 => ['file', (defined('PHP_WINDOWS_VERSION_BUILD') ? 'NUL' : '/dev/null'), 'w'], // stderr
		];

		$process = proc_open($command, $descriptors, $pipes);
		if(!is_resource($process)){
			throw new \RuntimeException('Unable to start PHP server.');
		}
		$this->process = $process;

		$this->waitForServer();
	}

	public function stop(): void{
		if(is_resource($this->process)){
			proc_terminate($this->process);
			proc_close($this->process);
			$this->process = null;
		}
	}

	public function __destruct(){
		$this->stop();
	}

	private function waitForServer(): void{
		for($i = 0; $i < 10; $i++){ // max 1 second
			$fp = @fsockopen($this->host, $this->port, $errno, $errstr, 0.1);
			if($fp !== false){
				fclose($fp);
				return;
			}

			usleep(100000); // 100ms
		}

		throw new \RuntimeException("Server failed to start on '" . $this->getUrl() . "'.");
	}

}
