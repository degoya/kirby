<template>
	<k-string-input
		v-bind="$props"
		type="text"
		class="k-text-input"
		@input="$emit('input', $event)"
		@invalid="($invalid, $v) => $emit('invalid', $invalid, $v)"
	/>
</template>

<script>
import StringInput, { props as StringInputProps } from "./StringInput.vue";

export const props = {
	mixins: [StringInputProps]
};

/**
 * @example <k-input :value="text" @input="text = $event" name="text" type="text" />
 */
export default {
	mixins: [StringInput, props]
};
</script>

<style>
.k-text-input {
	padding: var(--input-padding);
	border-radius: var(--input-rounded);
}
.k-text-input:focus {
	outline: 0;
}
.k-text-input[data-font="monospace"] {
	font-family: var(--font-mono);
}
</style>
