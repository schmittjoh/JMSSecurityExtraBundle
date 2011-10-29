<?php

namespace JMS\SecurityExtraBundle\Annotation;

/**
 * Annotation for expression-based access control.
 *
 * @Annotation
 * @Target("METHOD")
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class PreAuthorize
{
    /**
     * @Required
     * @var string
     */
    public $expr;

    public function __construct()
    {
        if (0 === func_num_args()) {
            return;
        }
        $values = func_get_arg(0);

        if (isset($values['value'])) {
            $values['expr'] = $values['value'];
        }
        if (!isset($values['expr'])) {
            throw new \InvalidArgumentException('The "expr" attribute must be set for annotation @PreAuthorize.');
        }

        if (!is_string($values['expr'])) {
            throw new \InvalidArgumentException(sprintf('The "expr" attribute of annotation @PreAuthorize must be a string, but got "%s".', gettype($values['expr'])));
        }

        $this->expr = $values['expr'];
    }
}