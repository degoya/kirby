/**
 * @since 4.0.0
 */
export default {
	created() {
		/**
		 * Delegate all required window events to the
		 * event emitter
		 */
		this.$panel.events.subscribe();

		/**
		 * Register all created plugins
		 */
		for (const plugin of this.$panel.plugins.created) {
			plugin(this);
		}

		/**
		 * Hook up the back button
		 */
		this.$panel.events.on("popstate", () => {
			this.$panel.open(window.location.href);
		});

		/**
		 * Clean drag & drop info
		 */
		this.$panel.events.on("drop", () => {
			this.$panel.drag.stop();
		});

		/**
		 * Setup the content store
		 */
		this.$store.dispatch("content/init");
	},
	destroyed() {
		this.$panel.events.unsubscribe();
	},
	render(h) {
		let view = this.$panel.view.component;

		if (view) {
			// TODO: this is a hack to make the preview work
			if (this.$panel.view.preview) {
				if (view === "k-page-view" || view === "k-site-view") {
					view = "k-preview-view";
				}
			}

			return h(view, {
				key: view,
				props: this.$panel.view.props
			});
		}
	}
};
