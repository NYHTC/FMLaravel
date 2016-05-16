<?php namespace FMLaravel\Database\ContainerField;

use FMLaravel\Database\Model;
use FMLaravel\Support\Script;

trait SetMutatorBase64Fields
{
    protected $containerFieldMutatorSetOrigin      = true;
    protected $containerFieldMutatorFilenameKey    = 'Filename';
    protected $containerFieldMutatorDataKey        = 'Base64';

    protected function containerFieldSetMutator($key, ContainerField $cf)
    {

        $this->attribute[$key . $this->containerFieldMutatorFilenameKey] = $cf->filename;
        $this->attribute[$key . $this->containerFieldMutatorDataKey] = base64_encode($cf->data);

        if ($this->containerFieldMutatorSetOrigin) {
            $this->attribute[$key] = $cf;
        }
        return $this;
    }
}
