<?php

namespace Voryx\ThruwayBundle\Annotation;

interface Annotation
{
    /**
     * @return mixed
     */
    public function getWorker();
}
