import { useState } from "react";
import clsx from "clsx";

export default function Tabs({ tabs }) {
    const [active, setActive] = useState(tabs[0].value);

    return (
        <div>
            <div className="flex space-x-2 border-b border-neutral-200 mb-4">
                {tabs.map((tab) => (
                    <button
                        key={tab.value}
                        onClick={() => setActive(tab.value)}
                        className={clsx(
                            "px-3 py-2 text-sm font-medium transition-colors",
                            active === tab.value
                                ? "border-b-2 border-neutral-900"
                                : "text-neutral-500 hover:text-neutral-900"
                        )}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {tabs.map(
                (tab) =>
                    tab.value === active && (
                        <div key={tab.value}>{tab.content}</div>
                    )
            )}
        </div>
    );
}