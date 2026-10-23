import clsx from "clsx";

export default function Section({
                                    children,
                                    spacing = "default",
                                    className,
                                }) {
    const spacings = {
        sm: "py-8",
        default: "py-12",
        lg: "py-20",
    };

    return (
        <section
            className={clsx(
                spacings[spacing],
                className
            )}
        >
            {children}
        </section>
    );
}