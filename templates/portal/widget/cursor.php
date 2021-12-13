                    <ul id="cursor_<?php echo $models['list']; ?>" class="cursor" data-list="<?php echo $models['list']; ?>" data-count="<?php echo $models['count']; ?>" data-limit="<?php echo $models['limit']; ?>" data-offset="<?php echo $models['offset']; ?>">
                    	<li class="step first">
                    		<i class="fa fw fa-backward" title="<?php echo $this->locale('portal')->cursor->first; ?>"></i>
                    	</li>
                    	<li class="step previous">
                    		<i class="fa fw fa-step-backward" title="<?php echo $this->locale('portal')->cursor->previous; ?>"></i>
                    	</li>
                    	<li class="index">
                    		<select>
<?php   for ($offset = 0 ; $offset < $models['count'] ; $offset += $models['limit']) { ?>
								<option value="<?php echo $offset; ?>"<?php echo $offset == $models['offset'] ? ' selected' : ''; ?>><?php echo $models['index'][$offset]; ?> &rarr; <?php echo $models['index'][min([$offset + $models['limit'], $models['count']]) - 1]; ?></option>
<?php   } ?>
							</select>
						</li>
                    	<li class="step next">
                    		<i class="fa fw fa-step-forward" title="<?php echo $this->locale('portal')->cursor->next; ?>"></i>
                    	</li>
                    	<li class="step last">
                    		<i class="fa fw fa-forward" title="<?php echo $this->locale('portal')->cursor->last; ?>"></i>
                    	</li>
                    </ul>
