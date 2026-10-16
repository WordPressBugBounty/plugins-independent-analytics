function controllerDefinitions(controllerModules, path = [], controllers = new Map()) {
    Object.entries(controllerModules).forEach(([name, value]) => {
        const controllerPath = [...path, name]

        if (value && typeof value === "object" && Object.prototype.hasOwnProperty.call(value, "default")) {
            const identifier = name
            const controllerConstructor = value.default

            if (typeof controllerConstructor !== "function") {
                throw new Error(`Stimulus controller "${controllerPath.join("/")}" must have a default class export.`)
            }

            if (controllers.has(identifier)) {
                throw new Error(
                    `Duplicate Stimulus controller identifier "${identifier}" found at "${controllers.get(identifier).path}" and "${controllerPath.join("/")}".`
                )
            }

            controllers.set(identifier, {
                path: controllerPath.join("/"),
                definition: {
                    identifier,
                    controllerConstructor,
                },
            })
            return
        }

        if (!value || typeof value !== "object") {
            throw new Error(`Stimulus controller "${controllerPath.join("/")}" must have a default class export.`)
        }

        controllerDefinitions(value, controllerPath, controllers)
    })

    return Array.from(controllers.values(), ({definition}) => definition)
}

export function loadStimulusControllers(application, controllerModules) {
    application.load(controllerDefinitions(controllerModules))
}
