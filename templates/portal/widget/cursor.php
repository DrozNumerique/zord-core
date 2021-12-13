                    <ul id="cursor_<?php echo $models['list']; ?>" class="cursor" data-list="<?php echo $models['list']; ?>" data-count="<?php echo $models['count']; ?>" data-limit="<?php echo $models['limit']; ?>" data-offset="<?php echo $models['offset']; ?>">
                    	<li class="step previous">
                    		<span><?php echo $this->locale('portal')->cursor->previous; ?></span>
                    	</li>
                    	<li class="index">
                    		<select>
<?php   for ($offset = 0 ; $offset < $models['count'] ; $offset += $models['limit']) { ?>
								<option value="<?php echo $offset; ?>"<?php echo $offset == $models['offset'] ? ' selected' : ''; ?>><?php echo $models['index'][$offset]; ?> &rarr; <?php echo $models['index'][min([$offset + $models['limit'], $models['count']]) - 1]; ?></option>
<?php   } ?>
							</select>
						</li>
                    	<li class="step next">
                    		<span><?php echo $this->locale('portal')->cursor->next; ?></span>
                    	</li>
                    </ul>
