<?php

namespace Shamaseen\Repository\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepositoryFilesGenerated
{
    use Dispatchable;
    use SerializesModels;

    public string $userPath;
    public string $basePath;
    public string $modelName;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $basePath, string $userPath, string $modelName)
    {
        $this->basePath = $basePath;
        $this->userPath = $userPath;
        $this->modelName = $modelName;
    }
}
