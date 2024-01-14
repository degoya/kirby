<template>
	<k-panel
		class="k-preview-view"
		:style="{
			'--preview-window-width': 'var(--preview-window-width-' + size + ')'
		}"
	>
		<div class="k-preview-toolbar">
			<k-button-group>
				<k-button :link="id" icon="angle-left" size="sm" variant="filled" />
				<h1>
					<k-button :dropdown="true" icon="title" @click="$refs.tree.toggle()">
						{{ model.title }}
					</k-button>
				</h1>
				<k-dropdown-content ref="tree" theme="light" class="k-preview-tree">
					<k-page-tree @click.native.stop @select="navigate" />
				</k-dropdown-content>
			</k-button-group>
			<k-toggles-input
				:options="[
					{ icon: 'mobile', value: 'sm' },
					{ icon: 'tablet', value: 'md' },
					{ icon: 'display', value: 'lg' }
				]"
				:value="size"
				class="k-preview-sizes"
				@input="size = $event"
			/>
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
				<iframe ref="iframe" :src="model.previewUrl" @load="load"></iframe>
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
	data() {
		return {
			isReloading: false,
			size: "lg"
		};
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
	created() {
		this.$events.on("model.update", this.reload);
		this.$events.on("page.changeTitle", this.reload);
		this.$events.on("page.changeStatus", this.reload);
		this.$events.on("page.sort", this.reload);
		this.$events.on("file.sort", this.reload);
	},
	destroyed() {
		this.$events.off("model.update", this.reload);
		this.$events.off("page.changeTitle", this.reload);
		this.$events.off("page.changeStatus", this.reload);
		this.$events.off("page.sort", this.reload);
		this.$events.off("file.sort", this.reload);
	},
	methods: {
		load(event) {
			const location = event.target.contentDocument?.location;

			if (!location) {
				return;
			}

			if (location.href !== this.model.previewUrl) {
				const path = location.pathname.replace("/", "");

				if (path === "") {
					return this.$panel.view.open("site/preview");
				}

				const url = this.$api.pages.url(path, "preview");
				this.$panel.view.open(url);
			}

			this.isReloading = false;
		},
		navigate(page) {
			this.$refs.tree.close();

			if (page.id === "/") {
				return this.$panel.view.open("site/preview");
			}

			const url = this.$api.pages.url(page.id, "preview");
			this.$panel.view.open(url);
		},
		reload() {
			if (this.isReloading === true) {
				return;
			}

			this.isReloading = true;
			this.$refs.iframe.contentWindow.location.reload();
		}
	}
};
</script>

<style>
.k-preview-view {
	--preview-window-width-sm: calc(420px + var(--spacing-12) * 2) 1fr;
	--preview-window-width-md: calc(768px + var(--spacing-12) * 2) 1fr;
	--preview-window-width-lg: 1fr 30rem;

	display: grid;
	grid-template-areas:
		"toolbar toolbar"
		"window editor";
	grid-template-columns: var(--preview-window-width);
	grid-template-rows: var(--height-xl) 1fr;
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

.k-preview-sizes {
	--field-input-height: var(--height-sm);
	background: transparent;
}
.k-preview-sizes label {
	background: var(--color-gray-300);
}

.k-preview-window {
	grid-area: window;
	padding: var(--spacing-12) var(--spacing-12) 0;
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
	border-left: 1px solid var(--color-border);
	container-type: inline-size;
}
</style>
