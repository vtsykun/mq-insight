<?php

namespace Okvpn\Bundle\MQInsightBundle\Provider;


interface TransformerToViewInterface
{
    public function transform(array $data);
}
