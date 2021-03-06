<?php

namespace Gist\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * class CreateCommand.
 *
 * @author Simon Vieille <simon@deblan.fr>
 */
class CreateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $types = implode(', ', $this->getTypes());
        $this
            ->setName('create')
            ->setDescription('Create a gist using the API')
            ->addArgument('input', InputArgument::REQUIRED, 'Input')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type', 'text')
            ->addOption('title', 't', InputOption::VALUE_REQUIRED, 'Title of the gist')
            ->addOption('show-url', 'u', InputOption::VALUE_NONE, 'Display only the gist url')
            ->addOption('show-id', 'i', InputOption::VALUE_NONE, 'Display only the gist Id')
            ->setHelp(<<<EOF
Provides a client to create a gist using the API.

Arguments:
    <info>input</info>
        Identify the source of the content: path of the file (eg: <comment>/path/to/file</comment>) or standard input (<comment>-</comment>)

    <info>type</info>
        Defines the type of code: {$types}
        Default value: <comment>text</comment>

Options:
    <info>--title</info>, <info>-t</info>
        Defines a title
    
    <info>--show-id</info>, <info>-i</info>
        Display only the Id of the gist

    <info>--show-url</info>, <info>-u</info>
        Display only the url of the gist
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$output->writeln(sprintf('<comment>%s</comment> bar.', 'test'));

        $file = $input->getArgument('input');
        $type = $input->getArgument('type');
        $title = $input->getOption('title');

        if ($file === '-') {
            $content = file_get_contents('php://stdin');
        } else {
            if (!is_readable($file)) {
                $output->writeln(sprintf('<error>%s: No such file.</error>', $file));

                return false;
            }

            if (!is_file($file)) {
                $output->writeln(sprintf('<error>"%s" must be a file.</error>', $file));

                return false;
            }

            $content = file_get_contents($file);
        }

        if (!in_array($type, $this->getTypes())) {
            $output->writeln(sprintf('<error>%s: invalid type.</error>', $type));

            return false;
        }

        if (trim($content) === '') {
            $output->writeln(sprintf('<error>You can not create an empty gist.</error>', $type));
        }

        $gist = $this->getSilexApplication()['api_client']->create($title, $type, $content);

        if ($input->getOption('show-url')) {
            $output->writeln($gist['url']);

            return true;
        }

        if ($input->getOption('show-id')) {
            $output->writeln($gist['gist']['Id']);

            return true;
        }

        $output->writeln(json_encode($gist));
    }

    /**
     * Returns the list of types.
     *
     * @return array
     */
    protected function getTypes()
    {
        $types = array(
            'html',
            'css',
            'javascript',
            'php',
            'sql',
            'xml',
            'yaml',
            'perl',
            'c',
            'asp',
            'python',
            'bash',
            'actionscript3',
            'text',
        );

        return $types;
    }
}
