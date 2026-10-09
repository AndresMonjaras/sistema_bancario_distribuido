<?php
namespace App\Services;
class RemoteError extends \RuntimeException {
 public function __construct(public string $errorCode,string $message,public int $status=503){parent::__construct($message);}
}
