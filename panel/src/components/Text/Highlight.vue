<script>
import Prism from "prismjs";

Prism.manual = true;

/**
 * @since 4.0.0
 * @internal
 */
export default {
	mounted() {
		Prism.highlightAll(this.$el);
	},
	updated() {
		Prism.highlightAll(this.$el);
	},
	render() {
		return this.$scopedSlots.default({});
	}
};
</script>

<style>
.token.punctuation,
.token.comment,
.token.doctype {
	color: var(--color-gray-500);
}
.token.tag,
.token.markup,
.token.variable,
.token.this,
.token.selector,
.token.key,
.token.kirbytag-bracket,
.token.prolog,
.token.delimiter {
	color: var(--color-red-500);
}
.token.constant,
.token.number,
.token.boolean,
.token.boolean.important,
.token.attr-name,
.token.kirbytag-attr,
.token.kirbytag-name,
.token.entity,
.token.bold,
.token.bold > .punctuation {
	color: var(--color-orange-500);
}
.token.keyword,
.token.italic,
.token.italic > .punctuation {
	color: var(--color-purple-500);
}
.token.function {
	color: var(--color-blue-500);
}
.token.operator,
.token.title {
	color: var(--color-aqua-500);
}
.token.string,
.token.attr-value,
.token.attr-value .punctuation,
.token.list.punctuation {
	color: var(--color-green-500);
}
.token.scope,
.token.class-name,
.token.property,
.token.url {
	color: var(--color-yellow-500);
}
.token.title,
.token.kirbytag-bracket,
.token.list.punctuation,
.token.bold {
	font-weight: var(--font-bold);
}
.token.title .punctuation {
	color: var(--color-gray-500);
}
.token.italic {
	font-style: italic;
}
</style>
