<?php $this->render('#input', ['field' => $field, 'type' => 'hidden', 'entry' => $entry ?? null, 'options' => $options]); ?>
          						<i class="display fa fa-fw <?php echo Zord::value('portal', ['states',$field,Zord::entryValue($entry ?? null, $field, $options)]); ?>"></i>
