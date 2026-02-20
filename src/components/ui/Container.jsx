import clsx from "clsx";

export default function Container({
                                      children,
                                      size = "default",
                                      className,
                                  }) {
    const sizes = {
        narrow: "max-w-2xl",
        default: "max-w-3xl", // Literary reading width
        wide: "max-w-5xl",
        full: "max-w-full",
    };

    return (
        <div
            className={clsx(
                "mx-auto w-full px-6",
                sizes[size],
                className
            )}
        >
            {children}
        </div>
    );
}