<?php

namespace JMS\SecurityExtraBundle\Annotation;

/**
 * This must be declared on classes which inherit from classes that have
 * requested method invocation securing capabilities.
 * 
 * It indicates to the analyzer that the developer is aware of these security
 * restrictions, and has applied them to the root class in an appropriate 
 * fashion.
 * 
 * We cannot do this automatically without properly analyzing the control flow,
 * and in some cases it is not possible at all. See the following example:
 * 
 * <code>
 *     // child class
 *     public function editComment($commentId)
 *     {
 *         // retrieve comment from database
 *         $comment = $this->entityManager->find($commentId);
 *         
 *         return parent::editComment($comment);
 *     }
 *     
 *     // base class which is inherited from
 *     /**
 *      * @SecureParam(name="comment", permissions="EDIT")
 *      *\/
 *     public function editComment(Comment $comment)
 *     {
 *        // do some supposedly secure action
 *     }
 * <code>
 * 
 * The above example can be rewritten so that we can apply security checks
 * automatically:
 * 
 * <code>
 * 		 // child class
 *     public function editComment($commentId)
 *     {
 *         // retrieve comment from database
 *         $comment = $this->entityManager->find($commentId);
 *         
 *         return $this->doEditComment($comment);
 *     }
 *     
 *     // base class which is inherited from
 *     /**
 *      * @SecureParam(name="comment", permissions="EDIT")
 *      *\/
 *     protected function doEditComment(Comment $comment)
 *     {
 *        // do some secure action
 *     }
 * </code>
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SatisfiesParentSecurityPolicy implements AnnotationInterface
{
    public function __construct(array $values)
    {
    }
}