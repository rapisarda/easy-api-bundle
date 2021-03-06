<?php


namespace EasyApiBundle\Util\Maker;


use EasyApiBundle\Util\StringUtils\CaseConverter;

class CrudGenerator extends AbstractGenerator
{
    /**
     * @var string
     */
    protected static $templatesDirectory = 'Doctrine/';

    /**
     * @param $bundle string
     * @param $context string
     * @param $entityName string
     * @param $parentEntityName string
     * @param $dumpExistingFiles boolean
     *
     * @return array paths to the generated files
     *
     * @throws \Twig\Error\Error
     */
    public function generate($bundle, $context, $entityName, $parentEntityName, $dumpExistingFiles = false)
    {
        $this->config = $this->loadEntityConfig($entityName, $bundle, $context);
        $paths = [];
        $paths['controller'] = $this->generateController($dumpExistingFiles);
        $paths['routing'] = $this->generateRouting($dumpExistingFiles);

        return $paths;
    }

    /**
     * Generate Controller file.
     *
     * @param $dumpExistingFiles boolean
     *
     * @return string
     *
     * @throws \Twig\Error\Error
     */
    protected function generateController($dumpExistingFiles)
    {
        $fileContent = $this->getContainer()->get('templating')->render(
            $this->getTemplatePath('doctrine/crud_controller.php.twig'),
            $this->generateContent()
        );

        return "{$this->container->getParameter('kernel.project_dir')}/".$this->writeFile($this->getControllerDirectoryPath(), $this->config->getEntityName().'Controller.php', $fileContent, $dumpExistingFiles);
    }

    /**
     * Generate routing file.
     *
     * @param $dumpExistingFiles boolean
     *
     * @return string
     *
     * @throws \Twig\Error\Error
     */
    protected function generateRouting($dumpExistingFiles)
    {
        // Generate general routing file
        $this->generateBundleRoutingFile($dumpExistingFiles);

        // Generate specific routing file
        $fileContent = $this->getContainer()->get('templating')->render(
            $this->getTemplatePath('doctrine/crud_routing.yml.twig'),
            $this->generateContent()
        );

        return "{$this->container->getParameter('kernel.project_dir')}/".$this->writeFile($this->getRoutingDirectoryPath(), $this->config->getEntityName().'.yml', $fileContent, $dumpExistingFiles);
    }

    /**
     * Generate the general routing file of the bundle to link the specific routing file.
     *
     * @param $dumpExistingFiles
     *
     * @return bool|int
     */
    protected function generateBundleRoutingFile($dumpExistingFiles)
    {
        $bundleName = $this->config->getBundleName();
        $routingFilePath = "src/{$bundleName}/Resources/config/";
        $routingFile = "{$routingFilePath}routing.yml";
        $content = file_exists($routingFile) ? file_get_contents($routingFile) : '';
        $dataContent = $this->generateContent();
        $routeName = $dataContent['route_name_prefix'].'_'.strtolower($dataContent['entity_route_name']);

        try {
            if (!preg_match("/{$routeName}/", $content)) {
                $content .= "\n\n".$this->getContainer()->get('templating')->render(
                        $this->getTemplatePath('doctrine/bundle_routing.yml.twig'),
                        $dataContent
                    );
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $this->writeFile($routingFilePath, 'routing.yml', $content, $dumpExistingFiles);
    }

    /**
     * @return string
     */
    protected function getControllerDirectoryPath()
    {
        $context = str_replace('\\', '/', $this->config->getContextName());

        return 'src/'.$this->config->getBundleName()."/Controller/{$context}";
    }

    /**
     * @return string
     */
    protected function getRoutingDirectoryPath()
    {
        $context = str_replace('\\', '/', $this->config->getContextName());

        return 'src/'.$this->config->getBundleName()."/Resources/config/routing/{$context}";
    }

    /**
     * @return string
     */
    protected function getRouteNamePrefix()
    {
        $bundleName = $this->config->getBundleName();
        $prefix = str_replace(['API', 'Bundle'], ['api_', ''], $bundleName);

        if(!empty($this->config->getContextName())) {
            return CaseConverter::convertToPascalCase($prefix.'_'.str_replace(['\\', '/'], '_', $this->config->getContextName()));
        }

        return CaseConverter::convertToPascalCase($prefix);
    }

    /**
     * @return array
     */
    protected function generateContent()
    {
        $transformedContext = str_replace('\\', '/', $this->config->getContextName());
        $bundle = $this->config->getBundleName();
        $context = str_replace('/', '\\', $this->config->getContextName());

        $uses = [
            $this->container->getParameter('easy_api.inheritance.controller'),
            $this->container->getParameter('easy_api.traits.crud'),
            "{$bundle}\\Entity\\".(!empty($context) ? "{$context}\\" : '').$this->config->getEntityName(),
            "{$bundle}\\Form\Type\\".(!empty($context) ? "{$context}\\" : '')."{$this->config->getEntityName()}Type",
        ];

        $content = [
            'namespace' => "{$bundle}\\Controller".(!empty($context) ? "\\{$context}" : ''),
            'parent' => EntityConfigLoader::getShortEntityType($this->container->getParameter('easy_api.inheritance.controller')),
            'entity_name' => $this->config->getEntityName(),
            'bundle_name' => $this->config->getBundleName(),
            'routing_url' => "{$this->config->getBundleName()}/Resources/config/routing/".(!empty($context) ? "{$transformedContext}/" : '')."{$this->config->getEntityName()}.yml",
            'context_name' => $context,
            'route_name_prefix' => $this->getRouteNamePrefix(),
            'entity_route_name' => CaseConverter::convertToPascalCase($this->config->getEntityName()),
            'entity_url_name' => str_replace('_', '-', CaseConverter::convertToPascalCase($this->config->getEntityName())),
            'serialization_groups' => implode(', ', $this->getSerializerGroups()),
            'uses' => $uses,
            'routingControllerPath' => "{$bundle}:".(!empty($context) ? "{$context}\\" : '').$this->config->getEntityName(),
            'nativeFieldsNames' => implode(', ', array_map(function($val) { return "'{$val}'"; }, $this->config->getNativeFieldsNames(false))),
        ];

        return $content;
    }

    /**
     * @return array
     */
    protected function getSerializerGroups()
    {
        $groups = ['\''.CaseConverter::convertToPascalCase($this->config->getEntityName()).'_full\''];

        // parent secrializer groups
        $parentConfig = $this->config->getParentEntity();
        if (null !== $parentConfig) {
            $groups[] = '\''.CaseConverter::convertToPascalCase($parentConfig->getEntityName()).'_full\'';
            foreach ($parentConfig->getFields() as $field) {
                if ($field->isReferential() && !in_array('\'referential_short\'', $groups)) {
                    $groups[] = '\'referential_short\'';
                } elseif (!$field->isNativeType() && ('manyToOne' === $field->getRelationType() || 'oneToOne' === $field->getRelationType())) {
                    $groups[] = '\''.CaseConverter::convertToPascalCase($field->getName()).'_id\'';
                }
            }
        }

        foreach ($this->config->getFields() as $field) {
            if ($field->isReferential()) {
                if(!in_array('\'referential_short\'', $groups)) {
                    $groups[] = '\'referential_short\'';
                }
            } elseif (!$field->isNativeType() && ('manyToOne' === $field->getRelationType() || 'oneToOne' === $field->getRelationType())) {
                $groups[] = '\''.CaseConverter::convertToPascalCase($field->getName()).'_id\'';
            }
        }

        return $groups;
    }
}
