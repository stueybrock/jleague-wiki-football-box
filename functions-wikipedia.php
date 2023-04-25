<?php
function string_to_wiki_link( $string ): string {
	return '[[' . $string . ']]';
}