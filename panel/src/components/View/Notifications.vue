<template>
	<k-button
		v-if="notification && notification.type !== 'error'"
		:icon="notification.icon"
		:text="notification.message"
		:theme="notification.theme"
		variant="filled"
		class="k-panel-notification"
		@click="notification.close()"
	/>
</template>

<script>
/**
 * @displayName PanelNotifications
 */
export default {
	computed: {
		notification() {
			if (
				this.$panel.notification.context === "view" &&
				!this.$panel.notification.isFatal
			) {
				return this.$panel.notification;
			}

			return null;
		}
	}
};
</script>
