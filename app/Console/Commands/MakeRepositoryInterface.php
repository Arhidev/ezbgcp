<?php

namespace App\Console\Commands;


use Illuminate\Console\GeneratorCommand;

class MakeRepositoryInterface extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository_int {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository interface class';

   /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'RepositoryInterface';


    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $path = parent::getPath($name);
        // get file name without extension
        $filename = substr($path, strrpos($path, "/") + 1, -4);

        //get the full path without the class name and without extension
        $base_path = substr($path, 0, strrpos($path, "/") + 1);

        return $base_path . $filename . "RepositoryInterface.php";
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return class_exists($rawName, false);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/repositoryInterface.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Repository';
    }

    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        // Do string replacement
        return str_replace('{{MyName}}', $class, $stub);
    }
}
