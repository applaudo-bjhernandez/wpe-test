import { a as f, r as m, b as n, m as u, c as M, s as c, U as g } from "./indexWPE-Bsx8TyLp.js";
const F = () => (i, d) => {
  const o = () => (r, t) => {
    r(
      f("afterFinalizeMigration", () => {
        r(c(g, Date.now()));
      })
    );
  }, a = () => (r, t) => {
    m.unstable_batchedUpdates(() => {
      r(
        n("wpmdbPreMigrationCheck", (e) => r(u(e)))
      ), r(
        n("addMigrationStages", (e) => r(M(e)))
      ), r(
        n("wpmdbFinalizeMigration", (e) => {
          const { enabled: s } = t().media_files;
          if (!s)
            return e;
          const l = t().profiles.current_profile;
          return e.profileID = l, e;
        })
      );
    });
  };
  i(o()), i(a());
};
export {
  F as default
};
//# sourceMappingURL=filters-94JtUC6I.js.map
