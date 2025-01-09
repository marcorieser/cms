<?php

//TODO[mr]: rename view (13.06.2024 mr)

namespace Statamic\Console\Commands;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Console\RunsInPlease;
use Symfony\Component\Console\Input\InputArgument;

use function Laravel\Prompts\error;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

//TODO[mr]: do we need to support backslashes in paths for windows? (14.06.2024 mr)

class MakeTemplate extends GeneratorCommand
{
    use RunsInPlease;

    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'statamic:make:template';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new template';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Template';

    /**
     * The stub to be used for generating the class.
     *
     * @var string
     */
    protected $stub = 'template.antlers.html.stub';

    protected $templateName;

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        try {
            $this
                ->promptForName()
//                ->cleanUpName()
                ->promptForPath();

            echo $this->templateName."\n";
        } catch (\Exception $exception) {
            error($exception->getMessage());

            return false;
        }
        //        $addon = $this->argument('addon');
        //        $path = $addon ? $this->getAddonPath($addon) : base_path();

        //        $data = [
        //            'name' => $this->getNameInput(),
        //        ];
        //
        //        $filename = Str::slug(Str::snake($this->getNameInput()));
        //
        //        $this->createFromStub(
        //            'widget.blade.php.stub',
        //            $path."/resources/views/widgets/{$filename}.blade.php",
        //            $data
        //        );

    }

    protected function promptForPath(): self
    {
        if (str($this->templateName)->contains('/')) {
            return $this;
        }

        $path = suggest(
            label: 'Where should the template be located at?',
            options: fn ($value) => $this->getViewSubdirectories(resource_path('views'))
                /*->filter(fn ($name) => Str::contains($name, $value, ignoreCase: true))*/,
            hint: 'Leave empty for template root.'
            //            validate: fn (string $value) => match (true) {
            //                //                ! $this->validateName($value) => 'The name contains invalid characters.',
            //                default => null
            //            },
        );
        return $this;
    }

    protected function promptForName(): self
    {
        if ($name = $this->argument('name')) {
            if (! $this->validateName($name)) {
                throw new \InvalidArgumentException('The name contains invalid characters.');
            }

            $this->templateName = $name;

            return $this;
        }

        $this->templateName = text(
            label: 'What should the template be named?',
            required: true,
            validate: fn (string $value) => match (true) {
                ! $this->validateName($value) => 'The name contains invalid characters.',
                default => null
            },
        );

        return $this;
    }

    protected function getViewSubdirectories($path): Collection
    {
        return collect(File::directories($path))
            ->flatMap(fn ($directory) => collect([$directory])
                ->merge($this->getViewSubdirectories($directory)))
            ->map(fn ($directory) => str_replace(resource_path('views').'/', '', $directory));
    }

    protected function validateName($name): bool
    {
        return (bool) preg_match('#^[a-z0-9_][a-z0-9.\-_/\\\]*$#', $name);
    }

    protected function cleanUpName(): self
    {
        $this->templateName = str($this->templateName)
            ->replace(['\\', '/'], '/')
            ->trim('/');

        return $this;
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, 'The name of the template'],
            ['addon', InputArgument::OPTIONAL, 'The package name of an addon (ie. john/my-addon)'],
        ];
    }
}
