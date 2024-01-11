<template>
	<k-panel class="k-preview-view">
		<div class="k-preview-toolbar">
			<h1>
				<k-button icon="title" @click="$dialog(id + '/changeTitle')">
					{{ model.title }}
				</k-button>
			</h1>
			<k-button-group>
				<k-button
					v-if="permissions.preview && model.previewUrl"
					:link="model.previewUrl"
					:title="$t('open')"
					icon="open"
					target="_blank"
					variant="filled"
					size="sm"
					class="k-page-view-preview"
				/>

				<k-button
					:disabled="isLocked === true"
					:dropdown="true"
					:title="$t('settings')"
					icon="cog"
					variant="filled"
					size="sm"
					class="k-page-view-options"
					@click="$refs.settings.toggle()"
				/>
				<k-dropdown-content
					ref="settings"
					:options="$dropdown(id)"
					align-x="end"
				/>

				<k-languages-dropdown />

				<k-button
					v-if="status"
					v-bind="statusBtn"
					class="k-page-view-status"
					variant="filled"
					@click="$dialog(id + '/changeStatus')"
				/>

				<k-form-buttons :lock="false" />
			</k-button-group>
		</div>

		<div class="k-preview-window">
			<div class="k-preview-window-frame">
				<iframe ref="iframe" :src="model.previewUrl"></iframe>
			</div>
		</div>

		<main class="k-preview-editor">
			<k-sections
				:blueprint="blueprint"
				:empty="$t('page.blueprint', { blueprint: $esc(blueprint) })"
				:lock="lock"
				:parent="id"
				:tab="tab"
			/>
		</main>

		<!-- Notifications -->
		<k-button
			v-if="notification && notification.type !== 'error'"
			:icon="notification.icon"
			:text="notification.message"
			:theme="notification.theme"
			variant="filled"
			class="k-panel-notification"
			@click="notification.close()"
		/>
	</k-panel>
</template>

<script>
import PageView from "./PageView.vue";

export default {
	extends: PageView,
	created() {
		this.$events.on("model.update", this.reload);
	},
	destroyed() {
		this.$events.off("model.update", this.reload);
	},
	computed: {
		notification() {
			if (
				this.$panel.notification.context === "view" &&
				!this.$panel.notification.isFatal
			) {
				return this.$panel.notification;
			}

			return null;
		},
		timestamp() {
			return this.$view.timestamp;
		}
	},
	watch: {
		timestamp() {
			this.reload();
		}
	},
	methods: {
		reload() {
			this.$refs.iframe.contentWindow.location.reload();
		}
	}
};
</script>

<style>
.k-preview-view {
	display: grid;
	grid-template-areas:
		"toolbar toolbar"
		"window editor";
	grid-template-columns: 2fr 1fr;
	grid-template-rows: var(--height-xl) auto;
	height: 100vh;
}

.k-preview-toolbar {
	grid-area: toolbar;
	z-index: var(--z-toolbar);
	background: var(--color-light);
	border-bottom: 1px solid var(--color-gray-300);
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding-inline: var(--spacing-2);
}
.k-preview-toolbar h1 {
	font-weight: var(--font-bold);
}

.k-preview-window {
	grid-area: window;
	padding: var(--spacing-12);
}
.k-preview-window-frame {
	height: 100%;
	background: var(--color-white);
	border-radius: var(--rounded-lg);
	overflow: hidden;
	box-shadow: var(--shadow-xl);
	position: relative;
}
.k-preview-window iframe {
	position: absolute;
	inset: 0;
	width: 100%;
	height: 100%;
}

.k-preview-editor {
	grid-area: editor;
	overflow: scroll;
	padding: var(--spacing-12);
}
</style>
