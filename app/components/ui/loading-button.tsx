import * as React from "react"
import { Button, ButtonProps } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { cn } from "@/lib/utils"

export interface LoadingButtonProps extends ButtonProps {
  isLoading?: boolean
  loadingText?: string
}

const LoadingButton = React.forwardRef<HTMLButtonElement, LoadingButtonProps>(
  ({ isLoading, loadingText, children, className, disabled, ...props }, ref) => {
    return (
      <Button
        ref={ref}
        disabled={isLoading || disabled}
        aria-busy={isLoading}
        className={cn("relative transition-all", className)}
        {...props}
      >
        {isLoading && (
          <Spinner className={cn("mr-2", !loadingText && "absolute left-1/2 -translate-x-1/2 mr-0")} />
        )}
        <span className={cn("inline-flex items-center gap-2", isLoading && !loadingText && "opacity-0")}>
          {isLoading && loadingText ? loadingText : children}
        </span>
      </Button>
    )
  }
)
LoadingButton.displayName = "LoadingButton"

export { LoadingButton }
